<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\RedisMessage;
use Enqueue\Test\ClassExtensionTrait;

class RedisMessageTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testCouldConstructMessageWithoutArguments()
    {
        $message = new RedisMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new RedisMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new RedisMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new RedisMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testCouldSetTimestampAsHeader()
    {
        $message = new RedisMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldSetReplyToAsHeader()
    {
        $message = new RedisMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame(['reply_to' => 'theQueueName'], $message->getHeaders());
    }
}
