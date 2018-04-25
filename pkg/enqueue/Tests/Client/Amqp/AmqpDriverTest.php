<?php

namespace Enqueue\Tests\Client\Amqp;

use Enqueue\Client\Amqp\AmqpDriver;
use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpProducer;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use PHPUnit\Framework\TestCase;

class AmqpDriverTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, AmqpDriver::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpDriver(
            $this->createAmqpContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );
    }

    public function testShouldReturnConfigObject()
    {
        $config = $this->createDummyConfig();

        $driver = new AmqpDriver($this->createAmqpContextMock(), $config, $this->createDummyQueueMetaRegistry());

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new AmqpQueue('aName');

        $context = $this->createAmqpContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new AmqpDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
        $this->assertSame([], $queue->getArguments());
        $this->assertSame(2, $queue->getFlags());
        $this->assertNull($queue->getConsumerTag());
    }

    public function testShouldCreateAndReturnQueueInstanceWithHardcodedTransportName()
    {
        $expectedQueue = new AmqpQueue('aName');

        $context = $this->createAmqpContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aBarQueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new AmqpDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aBarQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $transportMessage = new AmqpMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperties(['key' => 'val']);
        $transportMessage->setHeader('content_type', 'ContentType');
        $transportMessage->setHeader('expiration', '12345000');
        $transportMessage->setMessageId('MessageId');
        $transportMessage->setTimestamp(1000);
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');

        $driver = new AmqpDriver(
            $this->createAmqpContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $clientMessage = $driver->createClientMessage($transportMessage);

        $this->assertInstanceOf(Message::class, $clientMessage);
        $this->assertSame('body', $clientMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'content_type' => 'ContentType',
            'expiration' => '12345000',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $clientMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $clientMessage->getProperties());
        $this->assertSame('MessageId', $clientMessage->getMessageId());
        $this->assertSame(12345, $clientMessage->getExpire());
        $this->assertSame('ContentType', $clientMessage->getContentType());
        $this->assertSame(1000, $clientMessage->getTimestamp());
        $this->assertSame('theReplyTo', $clientMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $clientMessage->getCorrelationId());
    }

    public function testShouldThrowExceptionIfExpirationIsNotNumeric()
    {
        $transportMessage = new AmqpMessage();
        $transportMessage->setHeader('expiration', 'is-not-numeric');

        $driver = new AmqpDriver(
            $this->createAmqpContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('expiration header is not numeric. "is-not-numeric"');

        $driver->createClientMessage($transportMessage);
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setBody('body');
        $clientMessage->setHeaders(['hkey' => 'hval']);
        $clientMessage->setProperties(['key' => 'val']);
        $clientMessage->setContentType('ContentType');
        $clientMessage->setExpire(123);
        $clientMessage->setMessageId('MessageId');
        $clientMessage->setTimestamp(1000);
        $clientMessage->setReplyTo('theReplyTo');
        $clientMessage->setCorrelationId('theCorrelationId');

        $context = $this->createAmqpContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new AmqpMessage())
        ;

        $driver = new AmqpDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertInstanceOf(AmqpMessage::class, $transportMessage);
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
            'content_type' => 'ContentType',
            'delivery_mode' => 2,
            'expiration' => '123000',
        ], $transportMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $transportMessage->getProperties());
        $this->assertSame('MessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    public function testShouldSendMessageToRouter()
    {
        $topic = new AmqpTopic('');
        $transportMessage = new AmqpMessage();

        $producer = $this->createAmqpProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createAmqpContextMock();
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

        $driver = new AmqpDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldThrowExceptionIfTopicParameterIsNotSet()
    {
        $driver = new AmqpDriver(
            $this->createAmqpContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name parameter is required but is not set');

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $queue = new AmqpQueue('');
        $transportMessage = new AmqpMessage();

        $producer = $this->createAmqpProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createAmqpContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
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

        $driver = new AmqpDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aFooQueue');

        $driver->sendToProcessor($message);
    }

    public function testShouldThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $driver = new AmqpDriver(
            $this->createAmqpContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor name parameter is required but is not set');

        $driver->sendToProcessor(new Message());
    }

    public function testShouldThrowExceptionIfProcessorQueueNameParameterIsNotSet()
    {
        $driver = new AmqpDriver(
            $this->createAmqpContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSetupBroker()
    {
        $routerTopic = new AmqpTopic('');
        $routerQueue = new AmqpQueue('');

        $processorQueue = new AmqpQueue('');

        $context = $this->createAmqpContextMock();
        // setup router
        $context
            ->expects($this->at(0))
            ->method('createTopic')
            ->willReturn($routerTopic)
        ;
        $context
            ->expects($this->at(1))
            ->method('createQueue')
            ->willReturn($routerQueue)
        ;
        $context
            ->expects($this->at(2))
            ->method('declareTopic')
            ->with($this->identicalTo($routerTopic))
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
        // setup processor queue
        $context
            ->expects($this->at(5))
            ->method('createQueue')
            ->willReturn($processorQueue)
        ;
        $context
            ->expects($this->at(6))
            ->method('declareQueue')
            ->with($this->identicalTo($processorQueue))
        ;

        $meta = new QueueMetaRegistry($this->createDummyConfig(), [
            'default' => [],
        ]);

        $driver = new AmqpDriver(
            $context,
            $this->createDummyConfig(),
            $meta
        );

        $driver->setupBroker();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpContext
     */
    private function createAmqpContextMock()
    {
        return $this->createMock(AmqpContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpProducer
     */
    private function createAmqpProducerMock()
    {
        return $this->createMock(AmqpProducer::class);
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
