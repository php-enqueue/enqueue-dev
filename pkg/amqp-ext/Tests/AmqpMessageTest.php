<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\TestCase;

class AmqpMessageTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(PsrMessage::class, AmqpMessage::class);
    }

    public function testCouldBeConstructedWithoutArguments()
    {
        $message = new AmqpMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new AmqpMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldSetNoParamFlagInConstructor()
    {
        $message = new AmqpMessage();

        $this->assertSame(\AMQP_NOPARAM, $message->getFlags());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new AmqpMessage();
        $message->setCorrelationId('theCorrelationId');

        $this->assertSame(['correlation_id' => 'theCorrelationId'], $message->getHeaders());
    }

    public function testShouldSetSetMessageIdAsHeader()
    {
        $message = new AmqpMessage();
        $message->setMessageId('theMessageId');

        $this->assertSame(['message_id' => 'theMessageId'], $message->getHeaders());
    }

    public function testShouldSetTimestampAsHeader()
    {
        $message = new AmqpMessage();
        $message->setTimestamp('theTimestamp');

        $this->assertSame(['timestamp' => 'theTimestamp'], $message->getHeaders());
    }

    public function testShouldSetReplyToAsHeader()
    {
        $message = new AmqpMessage();
        $message->setReplyTo('theReply');

        $this->assertSame(['reply_to' => 'theReply'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetDeliveryTag()
    {
        $message = new AmqpMessage();

        $message->setDeliveryTag('theDeliveryTag');

        $this->assertSame('theDeliveryTag', $message->getDeliveryTag());
    }

    public function testShouldReturnPreviouslySetConsumerTag()
    {
        $message = new AmqpMessage();

        $message->setConsumerTag('theConsumerTag');

        $this->assertSame('theConsumerTag', $message->getConsumerTag());
    }

    public function testShouldAllowAddFlags()
    {
        $message = new AmqpMessage();

        $message->addFlag(AMQP_DURABLE);
        $message->addFlag(AMQP_PASSIVE);

        $this->assertSame(AMQP_DURABLE | AMQP_PASSIVE, $message->getFlags());
    }

    public function testShouldClearPreviouslySetFlags()
    {
        $message = new AmqpMessage();

        $message->addFlag(AMQP_DURABLE);
        $message->addFlag(AMQP_PASSIVE);

        //guard
        $this->assertSame(AMQP_DURABLE | AMQP_PASSIVE, $message->getFlags());

        $message->clearFlags();

        $this->assertSame(AMQP_NOPARAM, $message->getFlags());
    }
}
