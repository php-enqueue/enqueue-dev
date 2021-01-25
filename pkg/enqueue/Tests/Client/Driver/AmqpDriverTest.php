<?php

namespace Enqueue\Tests\Client\Driver;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Enqueue\Client\Config;
use Enqueue\Client\Driver\AmqpDriver;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpProducer;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use PHPUnit\Framework\TestCase;

class AmqpDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, AmqpDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, AmqpDriver::class);
    }

    public function testThrowIfPriorityIsNotSupportedOnCreateTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setPriority('invalidPriority');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($this->createMessage())
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cant convert client priority "invalidPriority" to transport one. Could be one of "enqueue.message_queue.client.very_low_message_priority", "enqueue.message_queue.client.low_message_priority", "enqueue.message_queue.client.normal_message_priority');
        $driver->createTransportMessage($clientMessage);
    }

    public function testShouldSetExpirationHeaderFromClientMessageExpireInMillisecondsOnCreateTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setExpire(333);

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($this->createMessage())
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        /** @var AmqpMessage $transportMessage */
        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertSame(333000, $transportMessage->getExpiration());
        $this->assertSame('333000', $transportMessage->getHeader('expiration'));
    }

    public function testShouldSetPersistedDeliveryModeOnCreateTransportMessage()
    {
        $clientMessage = new Message();

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($this->createMessage())
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        /** @var AmqpMessage $transportMessage */
        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertSame(AmqpMessage::DELIVERY_MODE_PERSISTENT, $transportMessage->getDeliveryMode());
    }

    public function testShouldCreateDurableQueue()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($this->createQueue('aName'))
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        /** @var AmqpQueue $queue */
        $queue = $driver->createQueue('aName');

        $this->assertSame(AmqpQueue::FLAG_DURABLE, $queue->getFlags());
    }

    public function testShouldResetPriorityAndExpirationAndNeverCallProducerDeliveryDelayOnSendMessageToRouter()
    {
        $topic = $this->createTopic('');
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $producer
            ->expects($this->never())
            ->method('setDeliveryDelay')
        ;

        $context = $this->createContextMock();
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setExpire(123);
        $message->setPriority(MessagePriority::HIGH);

        $driver->sendToRouter($message);

        $this->assertNull($transportMessage->getExpiration());
        $this->assertNull($transportMessage->getPriority());
    }

    public function testShouldSetupBroker()
    {
        $routerTopic = $this->createTopic('');
        $routerQueue = $this->createQueue('');
        $processorWithDefaultQueue = $this->createQueue('default');
        $processorWithCustomQueue = $this->createQueue('custom');
        $context = $this->createContextMock();
        // setup router
        $context
            ->expects($this->at(0))
            ->method('createTopic')
            ->willReturn($routerTopic)
        ;
        $context
            ->expects($this->at(1))
            ->method('declareTopic')
            ->with($this->identicalTo($routerTopic))
        ;

        $context
            ->expects($this->at(2))
            ->method('createQueue')
            ->willReturn($routerQueue)
        ;
        $context
            ->expects($this->at(3))
            ->method('declareQueue')
            ->with($this->identicalTo($routerQueue))
        ;

        $context
            ->expects($this->at(4))
            ->method('bind')
            ->with($this->isInstanceOf(AmqpBind::class))
        ;

        // setup processor with default queue
        $context
            ->expects($this->at(5))
            ->method('createQueue')
            ->with($this->getDefaultQueueTransportName())
            ->willReturn($processorWithDefaultQueue)
        ;
        $context
            ->expects($this->at(6))
            ->method('declareQueue')
            ->with($this->identicalTo($processorWithDefaultQueue))
        ;

        $context
            ->expects($this->at(7))
            ->method('createQueue')
            ->with($this->getCustomQueueTransportName())
            ->willReturn($processorWithCustomQueue)
        ;
        $context
            ->expects($this->at(8))
            ->method('declareQueue')
            ->with($this->identicalTo($processorWithCustomQueue))
        ;

        $driver = new AmqpDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('aTopic', Route::TOPIC, 'aProcessor'),
                new Route('aCommand', Route::COMMAND, 'aProcessor', ['queue' => 'custom']),
            ])
        );
        $driver->setupBroker();
    }

    public function testShouldNotDeclareSameQueues()
    {
        $context = $this->createContextMock();

        // setup processor with default queue
        $context
            ->expects($this->any())
            ->method('createTopic')
            ->willReturn($this->createTopic(''))
        ;
        $context
            ->expects($this->any())
            ->method('createQueue')
            ->willReturn($this->createQueue('custom'))
        ;
        $context
            ->expects($this->exactly(2))
            ->method('declareQueue')
        ;

        $driver = new AmqpDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('aTopic', Route::TOPIC, 'aProcessor', ['queue' => 'custom']),
                new Route('aCommand', Route::COMMAND, 'aProcessor', ['queue' => 'custom']),
            ])
        );
        $driver->setupBroker();
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new AmqpDriver(...$args);
    }

    /**
     * @return AmqpContext
     */
    protected function createContextMock(): Context
    {
        return $this->createMock(AmqpContext::class);
    }

    /**
     * @return AmqpProducer
     */
    protected function createProducerMock(): InteropProducer
    {
        return $this->createMock(AmqpProducer::class);
    }

    /**
     * @return AmqpQueue
     */
    protected function createQueue(string $name): InteropQueue
    {
        return new AmqpQueue($name);
    }

    protected function createTopic(string $name): AmqpTopic
    {
        return new AmqpTopic($name);
    }

    /**
     * @return AmqpMessage
     */
    protected function createMessage(): InteropMessage
    {
        return new AmqpMessage();
    }

    protected function getRouterTransportName(): string
    {
        return 'aprefix.router';
    }

    protected function assertTransportMessage(InteropMessage $transportMessage): void
    {
        $this->assertSame('body', $transportMessage->getBody());
        Assert::assertArraySubset([
            'hkey' => 'hval',
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => 'ContentType',
            'expiration' => '123000',
            'priority' => 3,
            'message_id' => 'theMessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
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
}
