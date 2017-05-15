<?php

namespace Enqueue\Stomp\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Stomp\Client\ManagementClient;
use Enqueue\Stomp\Client\RabbitMqStompDriver;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Enqueue\Stomp\StompProducer;
use Enqueue\Test\ClassExtensionTrait;
use Psr\Log\LoggerInterface;

class RabbitMqStompDriverTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, RabbitMqStompDriver::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );
    }

    public function testShouldReturnConfigObject()
    {
        $config = $this->createDummyConfig();

        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $config,
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new StompDestination('aName');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new RabbitMqStompDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
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

    public function testShouldCreateAndReturnQueueInstanceWithHardcodedTransportName()
    {
        $expectedQueue = new StompDestination('aName');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aBarQueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new RabbitMqStompDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $queue = $driver->createQueue('aBarQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $transportMessage = new StompMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperties(['key' => 'val']);
        $transportMessage->setHeader('content-type', 'ContentType');
        $transportMessage->setHeader('expiration', '12345000');
        $transportMessage->setHeader('priority', 3);
        $transportMessage->setHeader('x-delay', '5678000');
        $transportMessage->setMessageId('MessageId');
        $transportMessage->setTimestamp(1000);
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');

        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $clientMessage = $driver->createClientMessage($transportMessage);

        $this->assertInstanceOf(Message::class, $clientMessage);
        $this->assertSame('body', $clientMessage->getBody());
        $this->assertSame(['hkey' => 'hval'], $clientMessage->getHeaders());
        $this->assertSame(['key' => 'val'], $clientMessage->getProperties());
        $this->assertSame('MessageId', $clientMessage->getMessageId());
        $this->assertSame(12345, $clientMessage->getExpire());
        $this->assertSame(5678, $clientMessage->getDelay());
        $this->assertSame('ContentType', $clientMessage->getContentType());
        $this->assertSame(1000, $clientMessage->getTimestamp());
        $this->assertSame(MessagePriority::HIGH, $clientMessage->getPriority());
        $this->assertSame('theReplyTo', $clientMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $clientMessage->getCorrelationId());
    }

    public function testShouldThrowExceptionIfXDelayIsNotNumeric()
    {
        $transportMessage = new StompMessage();
        $transportMessage->setHeader('x-delay', 'is-not-numeric');

        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('x-delay header is not numeric. "is-not-numeric"');

        $driver->createClientMessage($transportMessage);
    }

    public function testShouldThrowExceptionIfExpirationIsNotNumeric()
    {
        $transportMessage = new StompMessage();
        $transportMessage->setHeader('expiration', 'is-not-numeric');

        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('expiration header is not numeric. "is-not-numeric"');

        $driver->createClientMessage($transportMessage);
    }

    public function testShouldThrowExceptionIfCantConvertTransportPriorityToClientPriority()
    {
        $transportMessage = new StompMessage();
        $transportMessage->setHeader('priority', 'unknown');

        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cant convert transport priority to client: "unknown"');

        $driver->createClientMessage($transportMessage);
    }

    public function testShouldThrowExceptionIfCantConvertClientPriorityToTransportPriority()
    {
        $clientMessage = new Message();
        $clientMessage->setPriority('unknown');

        $transportMessage = new StompMessage();

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new RabbitMqStompDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cant convert client priority to transport: "unknown"');

        $driver->createTransportMessage($clientMessage);
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setBody('body');
        $clientMessage->setHeaders(['hkey' => 'hval']);
        $clientMessage->setProperties(['key' => 'val']);
        $clientMessage->setContentType('ContentType');
        $clientMessage->setExpire(123);
        $clientMessage->setPriority(MessagePriority::VERY_HIGH);
        $clientMessage->setDelay(432);
        $clientMessage->setMessageId('MessageId');
        $clientMessage->setTimestamp(1000);
        $clientMessage->setReplyTo('theReplyTo');
        $clientMessage->setCorrelationId('theCorrelationId');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new StompMessage())
        ;

        $driver = new RabbitMqStompDriver(
            $context,
            new Config('', '', '', '', '', '', ['delay_plugin_installed' => true]),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertInstanceOf(StompMessage::class, $transportMessage);
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'content-type' => 'ContentType',
            'persistent' => true,
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply-to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
            'expiration' => '123000',
            'priority' => 4,
            'x-delay' => '432000',
        ], $transportMessage->getHeaders());
        $this->assertSame(['key' => 'val'], $transportMessage->getProperties());
        $this->assertSame('MessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    public function testShouldThrowExceptionIfDelayIsSetButDelayPluginInstalledOptionIsFalse()
    {
        $clientMessage = new Message();
        $clientMessage->setDelay(123);

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new StompMessage())
        ;

        $driver = new RabbitMqStompDriver(
            $context,
            new Config('', '', '', '', '', '', ['delay_plugin_installed' => false]),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message delaying is not supported. In order to use delay feature install RabbitMQ delay plugin.');

        $driver->createTransportMessage($clientMessage);
    }

    public function testShouldSendMessageToRouter()
    {
        $topic = new StompDestination('');
        $transportMessage = new StompMessage();

        $producer = $this->createPsrProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->willReturn($topic)
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

        $driver = new RabbitMqStompDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldThrowExceptionIfTopicParameterIsNotSet()
    {
        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name parameter is required but is not set');

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $queue = new StompDestination('');
        $transportMessage = new StompMessage();

        $producer = $this->createPsrProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($queue)
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

        $driver = new RabbitMqStompDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aFooQueue');

        $driver->sendToProcessor($message);
    }

    public function testShouldSendMessageToDelayExchangeIfDelaySet()
    {
        $queue = new StompDestination('');
        $delayTopic = new StompDestination('');
        $transportMessage = new StompMessage();

        $producer = $this->createPsrProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($delayTopic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createPsrContextMock();
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

        $driver = new RabbitMqStompDriver(
            $context,
            new Config('', '', '', '', '', '', ['delay_plugin_installed' => true]),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aFooQueue');
        $message->setDelay(10);

        $driver->sendToProcessor($message);
    }

    public function testShouldThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor name parameter is required but is not set');

        $driver->sendToProcessor(new Message());
    }

    public function testShouldThrowExceptionIfProcessorQueueNameParameterIsNotSet()
    {
        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldNotSetupBrokerIfManagementPluginInstalledOptionIsNotEnabled()
    {
        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            new Config('', '', '', '', '', '', ['management_plugin_installed' => false]),
            $this->createDummyQueueMetaRegistry(),
            $this->createManagementClientMock()
        );

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('[RabbitMqStompDriver] Could not setup broker. The option `management_plugin_installed` is not enabled. Please enable that option and install rabbit management plugin')
        ;

        $driver->setupBroker($logger);
    }

    public function testShouldSetupBroker()
    {
        $metaRegistry = $this->createDummyQueueMetaRegistry();

        $managementClient = $this->createManagementClientMock();
        $managementClient
            ->expects($this->at(0))
            ->method('declareExchange')
            ->with('prefix.routertopic', [
                'type' => 'fanout',
                'durable' => true,
                'auto_delete' => false,
            ])
        ;
        $managementClient
            ->expects($this->at(1))
            ->method('declareQueue')
            ->with('prefix.app.routerqueue', [
                'durable' => true,
                'auto_delete' => false,
                'arguments' => [
                    'x-max-priority' => 4,
                ],
            ])
        ;
        $managementClient
            ->expects($this->at(2))
            ->method('bind')
            ->with('prefix.routertopic', 'prefix.app.routerqueue', 'prefix.app.routerqueue')
        ;
        $managementClient
            ->expects($this->at(3))
            ->method('declareQueue')
            ->with('prefix.app.default', [
                'durable' => true,
                'auto_delete' => false,
                'arguments' => [
                    'x-max-priority' => 4,
                ],
            ])
        ;

        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            new Config('prefix', 'app', 'routerTopic', 'routerQueue', 'processorQueue', '', ['management_plugin_installed' => true]),
            $metaRegistry,
            $managementClient
        );

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->at(0))
            ->method('debug')
            ->with('[RabbitMqStompDriver] Declare router exchange: prefix.routertopic')
        ;
        $logger
            ->expects($this->at(1))
            ->method('debug')
            ->with('[RabbitMqStompDriver] Declare router queue: prefix.app.routerqueue')
        ;
        $logger
            ->expects($this->at(2))
            ->method('debug')
            ->with('[RabbitMqStompDriver] Bind router queue to exchange: prefix.app.routerqueue -> prefix.routertopic')
        ;
        $logger
            ->expects($this->at(3))
            ->method('debug')
            ->with('[RabbitMqStompDriver] Declare processor queue: prefix.app.default')
        ;

        $driver->setupBroker($logger);
    }

    public function testSetupBrokerShouldCreateDelayExchangeIfEnabled()
    {
        $metaRegistry = $this->createDummyQueueMetaRegistry();

        $managementClient = $this->createManagementClientMock();
        $managementClient
            ->expects($this->at(6))
            ->method('declareExchange')
            ->with('prefix.app.default.delayed', [
                'type' => 'x-delayed-message',
                'durable' => true,
                'auto_delete' => false,
                'arguments' => [
                    'x-delayed-type' => 'direct',
                ],
            ])
        ;
        $managementClient
            ->expects($this->at(7))
            ->method('bind')
            ->with('prefix.app.default.delayed', 'prefix.app.default', 'prefix.app.default')
        ;

        $driver = new RabbitMqStompDriver(
            $this->createPsrContextMock(),
            new Config('prefix', 'app', 'routerTopic', 'routerQueue', 'processorQueue', '', ['management_plugin_installed' => true, 'delay_plugin_installed' => true]),
            $metaRegistry,
            $managementClient
        );

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->at(6))
            ->method('debug')
            ->with('[RabbitMqStompDriver] Declare delay exchange: prefix.app.default.delayed')
        ;
        $logger
            ->expects($this->at(7))
            ->method('debug')
            ->with('[RabbitMqStompDriver] Bind processor queue to delay exchange: prefix.app.default -> prefix.app.default.delayed')
        ;

        $driver->setupBroker($logger);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StompContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(StompContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StompProducer
     */
    private function createPsrProducerMock()
    {
        return $this->createMock(StompProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagementClient
     */
    private function createManagementClientMock()
    {
        return $this->createMock(ManagementClient::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return QueueMetaRegistry
     */
    private function createDummyQueueMetaRegistry()
    {
        $registry = new QueueMetaRegistry($this->createDummyConfig(), []);
        $registry->add('default');
        $registry->add('aFooQueue');
        $registry->add('aBarQueue', 'aBarQueue');

        return $registry;
    }

    /**
     * @return Config
     */
    private function createDummyConfig()
    {
        return Config::create('aPrefix');
    }
}
