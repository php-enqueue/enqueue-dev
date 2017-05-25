<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsMessage;
use Enqueue\Test\ClassExtensionTrait;

class FsMessageTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementJsonSerializableInterface()
    {
        $this->assertClassImplements(\JsonSerializable::class, FsMessage::class);
    }

    public function testCouldConstructMessageWithoutArguments()
    {
        $message = new FsMessage('');

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
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

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new FsMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new FsMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testCouldSetTimestampAsHeader()
    {
        $message = new FsMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetReplyToAsHeader()
    {
        $message = new FsMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame(['reply_to' => 'theQueueName'], $message->getHeaders());
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
