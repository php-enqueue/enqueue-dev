<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\Driver\RabbitMqStompDriver;
use Enqueue\Client\Driver\StompDriver;
use Enqueue\Client\Driver\StompManagementClient;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Stomp\ExtensionType;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Enqueue\Stomp\StompProducer;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\TestLogger;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\Topic as InteropTopic;
use PHPUnit\Framework\TestCase;

class RabbitMqStompDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, RabbitMqStompDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, RabbitMqStompDriver::class);
    }

    public function testShouldBeSubClassOfStompDriver()
    {
        $this->assertClassExtends(StompDriver::class, RabbitMqStompDriver::class);
    }

    public function testShouldCreateAndReturnStompQueueInstance()
    {
        $expectedQueue = new StompDestination(ExtensionType::RABBITMQ);

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([]),
            $this->createManagementClientMock()
        );

        $queue = $driver->createQueue('aFooQueue');

        $expectedHeaders = [
            'durable' => true,
            'auto-delete' => false,
            'exclusive' => false,
            'x-max-priority' => 4,
        ];

        $this->assertSame($expectedQueue, $queue);
        $this->assertTrue($queue->isDurable());
        $this->assertFalse($queue->isAutoDelete());
        $this->assertFalse($queue->isExclusive());
        $this->assertSame($expectedHeaders, $queue->getHeaders());
    }

    public function testThrowIfClientPriorityInvalidOnCreateTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setPriority('unknown');

        $transportMessage = new StompMessage();

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([]),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cant convert client priority to transport: "unknown"');

        $driver->createTransportMessage($clientMessage);
    }

    public function testThrowIfDelayIsSetButDelayPluginInstalledOptionIsFalse()
    {
        $clientMessage = new Message();
        $clientMessage->setDelay(123);

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new StompMessage())
        ;

        $config = Config::create(
            'aPrefix',
            '.',
            '',
            null,
            null,
            null,
            null,
            ['delay_plugin_installed' => false]
        );

        $driver = $this->createDriver(
            $context,
            $config,
            new RouteCollection([]),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message delaying is not supported. In order to use delay feature install RabbitMQ delay plugin.');

        $driver->createTransportMessage($clientMessage);
    }

    public function testShouldSetXDelayHeaderIfDelayPluginInstalledOptionIsTrue()
    {
        $clientMessage = new Message();
        $clientMessage->setDelay(123);

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new StompMessage())
        ;

        $config = Config::create(
            'aPrefix',
            '.',
            '',
            null,
            null,
            null,
            null,
            ['delay_plugin_installed' => true]
        );

        $driver = $this->createDriver(
            $context,
            $config,
            new RouteCollection([]),
            $this->createManagementClientMock()
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertSame('123000', $transportMessage->getHeader('x-delay'));
    }

    public function testShouldInitDeliveryDelayIfDelayPropertyOnSendToProcessor()
    {
        $this->shouldSendMessageToDelayExchangeIfDelaySet();
    }

    public function shouldSendMessageToDelayExchangeIfDelaySet()
    {
        $queue = new StompDestination(ExtensionType::RABBITMQ);
        $queue->setStompName('queueName');

        $delayTopic = new StompDestination(ExtensionType::RABBITMQ);
        $delayTopic->setStompName('delayTopic');

        $transportMessage = new StompMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->exactly(2))
            ->method('setDeliveryDelay')
            ->with($this->logicalOr(10000, null))
        ;
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($delayTopic), $this->identicalTo($transportMessage))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($queue)
        ;
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->willReturn($delayTopic)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $config = Config::create(
            'aPrefix',
            '.',
            '',
            null,
            null,
            null,
            null,
            ['delay_plugin_installed' => true]
        );

        $driver = $this->createDriver(
            $context,
            $config,
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor'),
            ]),
            $this->createManagementClientMock()
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, 'processor');
        $message->setDelay(10);

        $driver->sendToProcessor($message);
    }

    public function testShouldNotSetupBrokerIfManagementPluginInstalledOptionIsNotEnabled()
    {
        $config = Config::create(
            'aPrefix',
            '.',
            '',
            null,
            null,
            null,
            null,
            ['management_plugin_installed' => false]
        );

        $driver = $this->createDriver(
            $this->createContextMock(),
            $config,
            new RouteCollection([]),
            $this->createManagementClientMock()
        );

        $logger = new TestLogger();

        $driver->setupBroker($logger);

        self::assertTrue(
            $logger->hasDebugThatContains(
                '[RabbitMqStompDriver] Could not setup broker. The option `management_plugin_installed` is not enabled. Please enable that option and install rabbit management plugin'
            )
        );
    }

    public function testShouldSetupBroker()
    {
        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor'),
        ]);

        $managementClient = $this->createManagementClientMock();
        $managementClient
            ->expects($this->once())
            ->method('declareExchange')
            ->with('aprefix.router', [
                'type' => 'fanout',
                'durable' => true,
                'auto_delete' => false,
            ])
        ;
        $managementClient
            ->expects($this->exactly(2))
            ->method('declareQueue')
            ->with('aprefix.default', [
                'durable' => true,
                'auto_delete' => false,
                'arguments' => [
                    'x-max-priority' => 4,
                ],
            ])
        ;
        $managementClient
            ->expects($this->once())
            ->method('bind')
            ->with('aprefix.router', 'aprefix.default', 'aprefix.default')
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function (string $name) {
                $destination = new StompDestination(ExtensionType::RABBITMQ);
                $destination->setType(StompDestination::TYPE_QUEUE);
                $destination->setStompName($name);

                return $destination;
            })
        ;

        $config = Config::create(
            'aPrefix',
            '.',
            '',
            null,
            null,
            null,
            null,
            ['delay_plugin_installed' => false, 'management_plugin_installed' => true]
        );

        $driver = $this->createDriver(
            $contextMock,
            $config,
            $routeCollection,
            $managementClient
        );

        $logger = new TestLogger();

        $driver->setupBroker($logger);

        self::assertTrue(
            $logger->hasDebugThatContains(
                '[RabbitMqStompDriver] Declare router exchange: aprefix.router'
            )
        );
        self::assertTrue(
            $logger->hasDebugThatContains(
                '[RabbitMqStompDriver] Declare router queue: aprefix.default'
            )
        );
        self::assertTrue(
            $logger->hasDebugThatContains(
                '[RabbitMqStompDriver] Bind router queue to exchange: aprefix.default -> aprefix.router'
            )
        );
        self::assertTrue(
            $logger->hasDebugThatContains(
                '[RabbitMqStompDriver] Declare processor queue: aprefix.default'
            )
        );
    }

    public function testSetupBrokerShouldCreateDelayExchangeIfEnabled()
    {
        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor'),
        ]);

        $managementClient = $this->createManagementClientMock();
        $invoked = $this->exactly(2);
        $managementClient
            ->expects($invoked)
            ->method('declareExchange')
            ->willReturnCallback(function (string $name, array $options) use ($invoked) {
                match ($invoked->getInvocationCount()) {
                    1 => $this->assertSame([
                        'aprefix.router',
                        ['type' => 'fanout', 'durable' => true, 'auto_delete' => false],
                    ], [$name, $options]),
                    2 => $this->assertSame([
                        'aprefix.default.delayed',
                        ['type' => 'x-delayed-message', 'durable' => true, 'auto_delete' => false, 'arguments' => ['x-delayed-type' => 'direct']],
                    ], [$name, $options]),
                };
            });

        $bindInvoked = $this->exactly(2);
        $managementClient
            ->expects($bindInvoked)
            ->method('bind')
            ->willReturnCallback(function (string $exchange, string $queue, ?string $routingKey = null, $arguments = null) use ($bindInvoked) {
                match ($bindInvoked->getInvocationCount()) {
                    1 => $this->assertSame(
                        ['aprefix.router', 'aprefix.default', 'aprefix.default', null],
                        [$exchange, $queue, $routingKey, $arguments],
                    ),
                    2 => $this->assertSame(
                        ['aprefix.default.delayed', 'aprefix.default', 'aprefix.default', null],
                        [$exchange, $queue, $routingKey, $arguments],
                    ),
                };
            })
        ;

        $config = Config::create(
            'aPrefix',
            '.',
            '',
            null,
            null,
            null,
            null,
            ['delay_plugin_installed' => true, 'management_plugin_installed' => true]
        );

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function (string $name) {
                $destination = new StompDestination(ExtensionType::RABBITMQ);
                $destination->setType(StompDestination::TYPE_QUEUE);
                $destination->setStompName($name);

                return $destination;
            })
        ;
        $contextMock
            ->expects($this->any())
            ->method('createTopic')
            ->willReturnCallback(function (string $name) {
                $destination = new StompDestination(ExtensionType::RABBITMQ);
                $destination->setType(StompDestination::TYPE_TOPIC);
                $destination->setStompName($name);

                return $destination;
            })
        ;

        $driver = $this->createDriver(
            $contextMock,
            $config,
            $routeCollection,
            $managementClient
        );

        $logger = new TestLogger();

        $driver->setupBroker($logger);

        self::assertTrue(
            $logger->hasDebugThatContains(
                '[RabbitMqStompDriver] Declare delay exchange: aprefix.default.delayed'
            )
        );
        self::assertTrue(
            $logger->hasDebugThatContains(
                '[RabbitMqStompDriver] Bind processor queue to delay exchange: aprefix.default -> aprefix.default.delayed'
            )
        );
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new RabbitMqStompDriver(
            $args[0],
            $args[1],
            $args[2],
            isset($args[3]) ? $args[3] : $this->createManagementClientMock()
        );
    }

    /**
     * @return StompContext
     */
    protected function createContextMock(): Context
    {
        return $this->createMock(StompContext::class);
    }

    /**
     * @return StompProducer
     */
    protected function createProducerMock(): InteropProducer
    {
        return $this->createMock(StompProducer::class);
    }

    /**
     * @return StompDestination
     */
    protected function createQueue(string $name): InteropQueue
    {
        $destination = new StompDestination(ExtensionType::RABBITMQ);
        $destination->setType(StompDestination::TYPE_QUEUE);
        $destination->setStompName($name);

        return $destination;
    }

    /**
     * @return StompDestination
     */
    protected function createTopic(string $name): InteropTopic
    {
        $destination = new StompDestination(ExtensionType::RABBITMQ);
        $destination->setType(StompDestination::TYPE_TOPIC);
        $destination->setStompName($name);

        return $destination;
    }

    /**
     * @return StompMessage
     */
    protected function createMessage(): InteropMessage
    {
        return new StompMessage();
    }

    protected function assertTransportMessage(InteropMessage $transportMessage): void
    {
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertEquals([
            'hkey' => 'hval',
            'message_id' => 'theMessageId',
            'timestamp' => 1000,
            'reply-to' => 'theReplyTo',
            'persistent' => true,
            'correlation_id' => 'theCorrelationId',
            'expiration' => '123000',
            'priority' => 3,
            'x-delay' => '345000',
        ], $transportMessage->getHeaders());
        $this->assertEquals([
            'pkey' => 'pval',
            Config::CONTENT_TYPE => 'ContentType',
            Config::PRIORITY => MessagePriority::HIGH,
            Config::EXPIRE => 123,
            Config::DELAY => 345,
        ], $transportMessage->getProperties());
        $this->assertSame('theMessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    protected function createDummyConfig(): Config
    {
        return Config::create(
            'aPrefix',
            '.',
            '',
            null,
            null,
            null,
            null,
            ['delay_plugin_installed' => true, 'management_plugin_installed' => true]
        );
    }

    protected function getRouterTransportName(): string
    {
        return '/topic/aprefix.router';
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createManagementClientMock(): StompManagementClient
    {
        return $this->createMock(StompManagementClient::class);
    }
}
