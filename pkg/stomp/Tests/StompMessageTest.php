<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\StompMessage;
use Enqueue\Test\ClassExtensionTrait;
use Stomp\Transport\Frame;

class StompMessageTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithoutArguments()
    {
        $message = new StompMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new StompMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
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

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new StompMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new StompMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
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

    public function testShouldSetReplyToAsHeader()
    {
        $message = new StompMessage();
        $message->setReplyTo('theQueueName');

        self::assertSame(['reply-to' => 'theQueueName'], $message->getHeaders());
    }

    public function testShouldUnsetHeaderIfNullPassed()
    {
        $message = new StompMessage();

        $message->setHeader('aHeader', 'aVal');

        // guard
        $this->assertSame('aVal', $message->getHeader('aHeader'));

        $message->setHeader('aHeader', null);

        $this->assertNull($message->getHeader('aHeader'));
        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldUnsetPropertyIfNullPassed()
    {
        $message = new StompMessage();

        $message->setProperty('aProperty', 'aVal');

        // guard
        $this->assertSame('aVal', $message->getProperty('aProperty'));

        $message->setProperty('aProperty', null);

        $this->assertNull($message->getProperty('aProperty'));
        $this->assertSame([], $message->getProperties());
    }
}
