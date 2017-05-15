<?php

namespace Enqueue\Sqs\Tests;

use Enqueue\Psr\PsrMessage;
use Enqueue\Sqs\SqsMessage;
use Enqueue\Test\ClassExtensionTrait;

class SqsMessageTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(PsrMessage::class, SqsMessage::class);
    }

    public function testCouldConstructMessageWithBody()
    {
        $message = new SqsMessage('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldConstructMessageWithProperties()
    {
        $message = new SqsMessage('', ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldConstructMessageWithHeaders()
    {
        $message = new SqsMessage('', [], ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetBody()
    {
        $message = new SqsMessage();
        $message->setBody('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldSetGetProperties()
    {
        $message = new SqsMessage();
        $message->setProperties(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldSetGetHeaders()
    {
        $message = new SqsMessage();
        $message->setHeaders(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetRedelivered()
    {
        $message = new SqsMessage();

        $message->setRedelivered(true);
        $this->assertTrue($message->isRedelivered());

        $message->setRedelivered(false);
        $this->assertFalse($message->isRedelivered());
    }

    public function testCouldSetGetCorrelationId()
    {
        $message = new SqsMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame('the-correlation-id', $message->getCorrelationId());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new SqsMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetGetMessageId()
    {
        $message = new SqsMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame('the-message-id', $message->getMessageId());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new SqsMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testCouldSetGetTimestamp()
    {
        $message = new SqsMessage();
        $message->setTimestamp(12345);

        $this->assertSame(12345, $message->getTimestamp());
    }

    public function testCouldSetTimestampAsHeader()
    {
        $message = new SqsMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldReturnNullAsDefaultReplyTo()
    {
        $message = new SqsMessage();

        $this->assertSame(null, $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyTo()
    {
        $message = new SqsMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame('theQueueName', $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyToAsHeader()
    {
        $message = new SqsMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame(['reply_to' => 'theQueueName'], $message->getHeaders());
    }

    public function testShouldAllowGetDelaySeconds()
    {
        $message = new SqsMessage();
        $message->setDelaySeconds(12345);

        $this->assertSame(12345, $message->getDelaySeconds());
    }

    public function testShouldAllowGetMessageDeduplicationId()
    {
        $message = new SqsMessage();
        $message->setMessageDeduplicationId('theId');

        $this->assertSame('theId', $message->getMessageDeduplicationId());
    }

    public function testShouldAllowGetMessageGroupId()
    {
        $message = new SqsMessage();
        $message->setMessageGroupId('theId');

        $this->assertSame('theId', $message->getMessageGroupId());
    }

    public function testShouldAllowGetReceiptHandle()
    {
        $message = new SqsMessage();
        $message->setReceiptHandle('theId');

        $this->assertSame('theId', $message->getReceiptHandle());
    }
}
