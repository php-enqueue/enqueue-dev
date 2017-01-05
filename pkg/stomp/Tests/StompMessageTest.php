<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Psr\Message;
use Enqueue\Stomp\StompMessage;
use Enqueue\Test\ClassExtensionTrait;
use Stomp\Transport\Frame;

class StompMessageTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(Message::class, StompMessage::class);
    }

    public function testCouldConstructMessageWithBody()
    {
        $message = new StompMessage('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldConstructMessageWithProperties()
    {
        $message = new StompMessage('', ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldConstructMessageWithHeaders()
    {
        $message = new StompMessage('', [], ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetBody()
    {
        $message = new StompMessage();
        $message->setBody('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldSetGetProperties()
    {
        $message = new StompMessage();
        $message->setProperties(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldSetGetHeaders()
    {
        $message = new StompMessage();
        $message->setHeaders(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetPersistent()
    {
        $message = new StompMessage();

        $message->setPersistent(true);
        $this->assertTrue($message->isPersistent());

        $message->setPersistent(false);
        $this->assertFalse($message->isPersistent());
    }

    public function testShouldSetPersistentAsHeader()
    {
        $message = new StompMessage();

        $message->setPersistent(true);
        $this->assertSame(['persistent' => true], $message->getHeaders());
    }

    public function testCouldSetGetRedelivered()
    {
        $message = new StompMessage();

        $message->setRedelivered(true);
        $this->assertTrue($message->isRedelivered());

        $message->setRedelivered(false);
        $this->assertFalse($message->isRedelivered());
    }

    public function testCouldSetGetCorrelationId()
    {
        $message = new StompMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame('the-correlation-id', $message->getCorrelationId());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new StompMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetGetMessageId()
    {
        $message = new StompMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame('the-message-id', $message->getMessageId());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new StompMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testCouldSetGetTimestamp()
    {
        $message = new StompMessage();
        $message->setTimestamp(12345);

        $this->assertSame(12345, $message->getTimestamp());
    }

    public function testCouldSetTimestampAsHeader()
    {
        $message = new StompMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testCouldSetGetFrame()
    {
        $message = new StompMessage();
        $message->setFrame($frame = new Frame());

        $this->assertSame($frame, $message->getFrame());
    }

    public function testShouldReturnNullAsDefaultReplyTo()
    {
        $message = new StompMessage();

        self::assertSame(null, $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyTo()
    {
        $message = new StompMessage();
        $message->setReplyTo('theQueueName');

        self::assertSame('theQueueName', $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyToAsHeader()
    {
        $message = new StompMessage();
        $message->setReplyTo('theQueueName');

        self::assertSame(['reply-to' => 'theQueueName'], $message->getHeaders());
    }
}
