<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpMessage;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\Tests\BasePsrMessageTest;
use Enqueue\Test\ClassExtensionTrait;

class AmqpMessageTest extends BasePsrMessageTest
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

    public function testShouldReturnPreviouslySetCorrelationId()
    {
        $message = new AmqpMessage();
        $message->setCorrelationId('theCorrelationId');

        $this->assertSame('theCorrelationId', $message->getCorrelationId());
        $this->assertSame(['correlation_id' => 'theCorrelationId'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetMessageId()
    {
        $message = new AmqpMessage();
        $message->setMessageId('theMessageId');

        $this->assertSame('theMessageId', $message->getMessageId());
        $this->assertSame(['message_id' => 'theMessageId'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetTimestamp()
    {
        $message = new AmqpMessage();
        $message->setTimestamp('theTimestamp');

        $this->assertSame('theTimestamp', $message->getTimestamp());
        $this->assertSame(['timestamp' => 'theTimestamp'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetReplyTo()
    {
        $message = new AmqpMessage();
        $message->setReplyTo('theReply');

        $this->assertSame('theReply', $message->getReplyTo());
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

    /**
     * @return PsrMessage
     */
    protected function createMessage()
    {
        return new AmqpMessage();
    }
}
