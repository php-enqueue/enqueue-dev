<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalMessage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class DbalMessageTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithoutArguments()
    {
        $message = new DbalMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new DbalMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldSetPriorityToNullInConstructor()
    {
        $message = new DbalMessage();

        $this->assertNull($message->getPriority());
    }

    public function testShouldSetDelayToNullInConstructor()
    {
        $message = new DbalMessage();

        $this->assertNull($message->getDeliveryDelay());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new DbalMessage();
        $message->setCorrelationId('theCorrelationId');

        $this->assertSame(['correlation_id' => 'theCorrelationId'], $message->getHeaders());
    }

    public function testShouldSetPublishedAtToNullInConstructor()
    {
        $message = new DbalMessage();

        $this->assertNull($message->getPublishedAt());
    }

    public function testShouldSetMessageIdAsHeader()
    {
        $message = new DbalMessage();
        $message->setMessageId('theMessageId');

        $this->assertSame(['message_id' => 'theMessageId'], $message->getHeaders());
    }

    public function testShouldSetTimestampAsHeader()
    {
        $message = new DbalMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldSetReplyToAsHeader()
    {
        $message = new DbalMessage();
        $message->setReplyTo('theReply');

        $this->assertSame(['reply_to' => 'theReply'], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetPublishedAtTime()
    {
        $message = new DbalMessage();

        $message->setPublishedAt(123);

        $this->assertSame(123, $message->getPublishedAt());
    }
}
