<?php

namespace Enqueue\Null\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Null\Client\NullDriver;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullProducer;
use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use PHPUnit\Framework\TestCase;

class NullDriverTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new NullDriver(new NullContext(), Config::create(), $this->createDummyQueueMetaRegistry());
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new NullQueue('aName');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new NullDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithHardcodedTransportName()
    {
        $expectedQueue = new NullQueue('aName');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aBarQueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new NullDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aBarQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldSendMessageToRouter()
    {
        $config = Config::create();
        $topic = new NullTopic('topic');

        $transportMessage = new NullMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($topic), self::identicalTo($transportMessage))
        ;

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->willReturn($topic)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;

        $driver = new NullDriver($context, $config, $this->createDummyQueueMetaRegistry());

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $config = Config::create();
        $queue = new NullQueue('');

        $transportMessage = new NullMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($transportMessage))
        ;

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($queue)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;

        $driver = new NullDriver($context, $config, $this->createDummyQueueMetaRegistry());

        $driver->sendToProcessor(new Message());
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $config = Config::create();

        $clientMessage = new Message();
        $clientMessage->setBody('theBody');
        $clientMessage->setContentType('theContentType');
        $clientMessage->setMessageId('theMessageId');
        $clientMessage->setTimestamp(12345);
        $clientMessage->setDelay(123);
        $clientMessage->setExpire(345);
        $clientMessage->setPriority(MessagePriority::LOW);
        $clientMessage->setHeaders(['theHeaderFoo' => 'theFoo']);
        $clientMessage->setProperties(['thePropertyBar' => 'theBar']);
        $clientMessage->setReplyTo('theReplyTo');
        $clientMessage->setCorrelationId('theCorrelationId');

        $transportMessage = new NullMessage();

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new NullDriver($context, $config, $this->createDummyQueueMetaRegistry());

        $transportMessage = $driver->createTransportMessage($clientMessage);

        self::assertSame('theBody', $transportMessage->getBody());
        self::assertSame([
            'theHeaderFoo' => 'theFoo',
            'content_type' => 'theContentType',
            'expiration' => 345,
            'delay' => 123,
            'priority' => MessagePriority::LOW,
            'timestamp' => 12345,
            'message_id' => 'theMessageId',
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $transportMessage->getHeaders());
        self::assertSame([
            'thePropertyBar' => 'theBar',
        ], $transportMessage->getProperties());

        $this->assertSame('theMessageId', $transportMessage->getMessageId());
        $this->assertSame(12345, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $config = Config::create();

        $transportMessage = new NullMessage();
        $transportMessage->setBody('theBody');
        $transportMessage->setHeaders(['theHeaderFoo' => 'theFoo']);
        $transportMessage->setTimestamp(12345);
        $transportMessage->setMessageId('theMessageId');
        $transportMessage->setHeader('priority', MessagePriority::LOW);
        $transportMessage->setHeader('content_type', 'theContentType');
        $transportMessage->setHeader('delay', 123);
        $transportMessage->setHeader('expiration', 345);
        $transportMessage->setProperties(['thePropertyBar' => 'theBar']);
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');

        $driver = new NullDriver($this->createPsrContextMock(), $config, $this->createDummyQueueMetaRegistry());

        $clientMessage = $driver->createClientMessage($transportMessage);

        self::assertSame('theBody', $clientMessage->getBody());
        self::assertSame(MessagePriority::LOW, $clientMessage->getPriority());
        self::assertSame('theContentType', $clientMessage->getContentType());
        self::assertSame(123, $clientMessage->getDelay());
        self::assertSame(345, $clientMessage->getExpire());
        self::assertEquals([
            'theHeaderFoo' => 'theFoo',
            'content_type' => 'theContentType',
            'expiration' => 345,
            'delay' => 123,
            'priority' => MessagePriority::LOW,
            'timestamp' => 12345,
            'message_id' => 'theMessageId',
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $clientMessage->getHeaders());
        self::assertSame([
            'thePropertyBar' => 'theBar',
        ], $clientMessage->getProperties());

        $this->assertSame('theReplyTo', $clientMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $clientMessage->getCorrelationId());
    }

    public function testShouldReturnConfigInstance()
    {
        $config = Config::create();

        $driver = new NullDriver($this->createPsrContextMock(), $config, $this->createDummyQueueMetaRegistry());
        $result = $driver->getConfig();

        self::assertSame($config, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NullContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(NullContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NullProducer
     */
    private function createMessageProducer()
    {
        return $this->createMock(NullProducer::class);
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
