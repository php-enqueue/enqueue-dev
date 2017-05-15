<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Psr\PsrMessage;
use Enqueue\Redis\RedisMessage;
use Enqueue\Test\ClassExtensionTrait;

class RedisMessageTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(PsrMessage::class, RedisMessage::class);
    }

    public function testShouldImplementJsonSerializableInterface()
    {
        $this->assertClassImplements(\JsonSerializable::class, RedisMessage::class);
    }

    public function testCouldConstructMessageWithBody()
    {
        $message = new RedisMessage('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldConstructMessageWithProperties()
    {
        $message = new RedisMessage('', ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldConstructMessageWithHeaders()
    {
        $message = new RedisMessage('', [], ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetBody()
    {
        $message = new RedisMessage();
        $message->setBody('body');

        $this->assertSame('body', $message->getBody());
    }

    public function testCouldSetGetProperties()
    {
        $message = new RedisMessage();
        $message->setProperties(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testCouldSetGetHeaders()
    {
        $message = new RedisMessage();
        $message->setHeaders(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $message->getHeaders());
    }

    public function testCouldSetGetRedelivered()
    {
        $message = new RedisMessage();

        $message->setRedelivered(true);
        $this->assertTrue($message->isRedelivered());

        $message->setRedelivered(false);
        $this->assertFalse($message->isRedelivered());
    }

    public function testCouldSetGetCorrelationId()
    {
        $message = new RedisMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame('the-correlation-id', $message->getCorrelationId());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new RedisMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetGetMessageId()
    {
        $message = new RedisMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame('the-message-id', $message->getMessageId());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new RedisMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testCouldSetGetTimestamp()
    {
        $message = new RedisMessage();
        $message->setTimestamp(12345);

        $this->assertSame(12345, $message->getTimestamp());
    }

    public function testCouldSetTimestampAsHeader()
    {
        $message = new RedisMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldReturnNullAsDefaultReplyTo()
    {
        $message = new RedisMessage();

        $this->assertSame(null, $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyTo()
    {
        $message = new RedisMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame('theQueueName', $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyToAsHeader()
    {
        $message = new RedisMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame(['reply_to' => 'theQueueName'], $message->getHeaders());
    }

    public function testColdBeSerializedToJson()
    {
        $message = new RedisMessage('theBody', ['thePropFoo' => 'thePropFooVal'], ['theHeaderFoo' => 'theHeaderFooVal']);

        $this->assertEquals('{"body":"theBody","properties":{"thePropFoo":"thePropFooVal"},"headers":{"theHeaderFoo":"theHeaderFooVal"}}', json_encode($message));
    }

    public function testCouldBeUnserializedFromJson()
    {
        $message = new RedisMessage('theBody', ['thePropFoo' => 'thePropFooVal'], ['theHeaderFoo' => 'theHeaderFooVal']);

        $json = json_encode($message);

        //guard
        $this->assertNotEmpty($json);

        $unserializedMessage = RedisMessage::jsonUnserialize($json);

        $this->assertInstanceOf(RedisMessage::class, $unserializedMessage);
        $this->assertEquals($message, $unserializedMessage);
    }

    public function testThrowIfMalformedJsonGivenOnUnsterilizedFromJson()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');

        RedisMessage::jsonUnserialize('{]');
    }
}
