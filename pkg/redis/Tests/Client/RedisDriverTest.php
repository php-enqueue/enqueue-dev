<?php

namespace Enqueue\Redis\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Psr\PsrProducer;
use Enqueue\Redis\Client\RedisDriver;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class RedisDriverTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, RedisDriver::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new RedisDriver(
            $this->createPsrContextMock(),
            Config::create(),
            $this->createDummyQueueMetaRegistry()
        );
    }

    public function testShouldReturnConfigObject()
    {
        $config = Config::create();

        $driver = new RedisDriver($this->createPsrContextMock(), $config, $this->createDummyQueueMetaRegistry());

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new RedisDestination('aName');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new RedisDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithHardcodedTransportName()
    {
        $expectedQueue = new RedisDestination('aName');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aBarQueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new RedisDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aBarQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $transportMessage = new RedisMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperties(['key' => 'val']);
        $transportMessage->setHeader('content_type', 'ContentType');
        $transportMessage->setMessageId('MessageId');
        $transportMessage->setTimestamp(1000);
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');

        $driver = new RedisDriver(
            $this->createPsrContextMock(),
            Config::create(),
            $this->createDummyQueueMetaRegistry()
        );

        $clientMessage = $driver->createClientMessage($transportMessage);

        $this->assertInstanceOf(Message::class, $clientMessage);
        $this->assertSame('body', $clientMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'content_type' => 'ContentType',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $clientMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $clientMessage->getProperties());
        $this->assertSame('MessageId', $clientMessage->getMessageId());
        $this->assertSame('ContentType', $clientMessage->getContentType());
        $this->assertSame(1000, $clientMessage->getTimestamp());
        $this->assertSame('theReplyTo', $clientMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $clientMessage->getCorrelationId());

        $this->assertNull($clientMessage->getExpire());
        $this->assertSame(MessagePriority::NORMAL, $clientMessage->getPriority());
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
        $clientMessage->setMessageId('MessageId');
        $clientMessage->setTimestamp(1000);
        $clientMessage->setReplyTo('theReplyTo');
        $clientMessage->setCorrelationId('theCorrelationId');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new RedisMessage())
        ;

        $driver = new RedisDriver(
            $context,
            Config::create(),
            $this->createDummyQueueMetaRegistry()
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertInstanceOf(RedisMessage::class, $transportMessage);
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'content_type' => 'ContentType',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $transportMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $transportMessage->getProperties());
        $this->assertSame('MessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    public function testShouldSendMessageToRouterQueue()
    {
        $topic = new RedisDestination('aDestinationName');
        $transportMessage = new RedisMessage();
        $config = $this->createDummyConfig();

        $producer = $this->createPsrProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.default')
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

        $driver = new RedisDriver(
            $context,
            $config,
            $this->createDummyQueueMetaRegistry()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldThrowExceptionIfTopicParameterIsNotSet()
    {
        $driver = new RedisDriver(
            $this->createPsrContextMock(),
            Config::create(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name parameter is required but is not set');

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $queue = new RedisDestination('aDestinationName');
        $transportMessage = new RedisMessage();

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

        $driver = new RedisDriver(
            $context,
            Config::create(),
            $this->createDummyQueueMetaRegistry()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aFooQueue');

        $driver->sendToProcessor($message);
    }

    public function testShouldThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $driver = new RedisDriver(
            $this->createPsrContextMock(),
            Config::create(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor name parameter is required but is not set');

        $driver->sendToProcessor(new Message());
    }

    public function testShouldThrowExceptionIfProcessorQueueNameParameterIsNotSet()
    {
        $driver = new RedisDriver(
            $this->createPsrContextMock(),
            Config::create(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldDoNothingOnSetupBroker()
    {
        $context = $this->createPsrContextMock();
        // setup router
        $context
            ->expects($this->never())
            ->method('createTopic')
        ;
        $context
            ->expects($this->never())
            ->method('createQueue')
        ;

        $meta = new QueueMetaRegistry(Config::create(), [
            'default' => [],
        ]);

        $driver = new RedisDriver(
            $context,
            Config::create(),
            $meta
        );

        $driver->setupBroker();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RedisContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(RedisContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProducer
     */
    private function createPsrProducerMock()
    {
        return $this->createMock(PsrProducer::class);
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
