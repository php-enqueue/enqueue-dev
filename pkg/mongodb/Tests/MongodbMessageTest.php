<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbMessage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 */
class MongodbMessageTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithoutArguments()
    {
        $message = new MongodbMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new MongodbMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldSetNullPriorityInConstructor()
    {
        $message = new MongodbMessage();

        $this->assertNull($message->getPriority());
    }

    public function testShouldSetDelayToNullInConstructor()
    {
        $message = new MongodbMessage();

        $this->assertNull($message->getDeliveryDelay());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new MongodbMessage();
        $message->setCorrelationId('theCorrelationId');

        $this->assertSame(['correlation_id' => 'theCorrelationId'], $message->getHeaders());
    }

    public function testShouldSetPublishedAtToNullInConstructor()
    {
        $message = new MongodbMessage();

        $this->assertNull($message->getPublishedAt());
    }

    public function testShouldSetMessageIdAsHeader()
    {
        $message = new MongodbMessage();
        $message->setMessageId('theMessageId');

        $this->assertSame(['message_id' => 'theMessageId'], $message->getHeaders());
    }

    public function testShouldSetTimestampAsHeader()
    {
        $message = new MongodbMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldSetReplyToAsHeader()
    {
        $message = new MongodbMessage();
        $message->setReplyTo('theReply');

        $this->assertSame(['reply_to' => 'theReply'], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetPublishedAtTime()
    {
        $message = new MongodbMessage();

        $message->setPublishedAt(123);

        $this->assertSame(123, $message->getPublishedAt());
    }
}
