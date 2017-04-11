<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\NullDriver;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;
use Enqueue\Transport\Null\NullProducer;
use Enqueue\Transport\Null\NullQueue;
use Enqueue\Transport\Null\NullTopic;

class NullDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new NullDriver(new NullContext(), new Config('', '', '', '', '', ''));
    }

    public function testShouldSendMessageToRouter()
    {
        $config = new Config('', '', '', '', '', '');
        $topic = new NullTopic('topic');

        $transportMessage = new NullMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($topic), self::identicalTo($transportMessage))
        ;

        $context = $this->createContextMock();
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

        $driver = new NullDriver($context, $config);

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $config = new Config('', '', '', '', '', '');
        $queue = new NullQueue('');

        $transportMessage = new NullMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($transportMessage))
        ;

        $context = $this->createContextMock();
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

        $driver = new NullDriver($context, $config);

        $driver->sendToProcessor(new Message());
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $config = new Config('', '', '', '', '', '');

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

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new NullDriver($context, $config);

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
        $config = new Config('', '', '', '', '', '');

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

        $driver = new NullDriver($this->createContextMock(), $config);

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
        $config = new Config('', '', '', '', '', '');

        $driver = new NullDriver($this->createContextMock(), $config);
        $result = $driver->getConfig();

        self::assertSame($config, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NullContext
     */
    private function createContextMock()
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
}
