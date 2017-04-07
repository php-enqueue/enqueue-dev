<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsMessage;
use Enqueue\Psr\PsrMessage;
use Enqueue\Test\ClassExtensionTrait;

class FsMessageTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(PsrMessage::class, FsMessage::class);
    }

    public function testShouldImplementJsonSerializableInterface()
    {
        $this->assertClassImplements(\JsonSerializable::class, FsMessage::class);
    }

    public function testCouldConstructMessageWithBody()
    {
        $message = new FsMessage('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldConstructMessageWithProperties()
    {
        $message = new FsMessage('', ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldConstructMessageWithHeaders()
    {
        $message = new FsMessage('', [], ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetBody()
    {
        $message = new FsMessage();
        $message->setBody('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldSetGetProperties()
    {
        $message = new FsMessage();
        $message->setProperties(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldSetGetHeaders()
    {
        $message = new FsMessage();
        $message->setHeaders(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetRedelivered()
    {
        $message = new FsMessage();

        $message->setRedelivered(true);
        $this->assertTrue($message->isRedelivered());

        $message->setRedelivered(false);
        $this->assertFalse($message->isRedelivered());
    }

    public function testCouldSetGetCorrelationId()
    {
        $message = new FsMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame('the-correlation-id', $message->getCorrelationId());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new FsMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetGetMessageId()
    {
        $message = new FsMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame('the-message-id', $message->getMessageId());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new FsMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testCouldSetGetTimestamp()
    {
        $message = new FsMessage();
        $message->setTimestamp(12345);

        $this->assertSame(12345, $message->getTimestamp());
    }

    public function testCouldSetTimestampAsHeader()
    {
        $message = new FsMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldReturnNullAsDefaultReplyTo()
    {
        $message = new FsMessage();

        $this->assertSame(null, $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyTo()
    {
        $message = new FsMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame('theQueueName', $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyToAsHeader()
    {
        $message = new FsMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame(['reply-to' => 'theQueueName'], $message->getHeaders());
    }

    public function testColdBeSerializedToJson()
    {
        $message = new FsMessage('theBody', ['thePropFoo' => 'thePropFooVal'], ['theHeaderFoo' => 'theHeaderFooVal']);

        $this->assertEquals('{"body":"theBody","properties":{"thePropFoo":"thePropFooVal"},"headers":{"theHeaderFoo":"theHeaderFooVal"}}', json_encode($message));
    }

    public function testCouldBeUnserializedFromJson()
    {
        $message = new FsMessage('theBody', ['thePropFoo' => 'thePropFooVal'], ['theHeaderFoo' => 'theHeaderFooVal']);

        $json = json_encode($message);

        //guard
        $this->assertNotEmpty($json);

        $unserializedMessage = FsMessage::jsonUnserialize($json);

        $this->assertInstanceOf(FsMessage::class, $unserializedMessage);
        $this->assertEquals($message, $unserializedMessage);
    }

    public function testThrowIfMalformedJsonGivenOnUnsterilizedFromJson()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');

        FsMessage::jsonUnserialize('{]');
    }
}
