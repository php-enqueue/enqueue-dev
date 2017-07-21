<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpMessage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class AmqpMessageTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldReturnPreviouslySetDeliveryTag()
    {
        $message = new AmqpMessage();

        $message->setDeliveryTag('theDeliveryTag');

        $this->assertSame('theDeliveryTag', $message->getDeliveryTag());
    }

    public function testShouldAllowGetPreviouslySetMandatory()
    {
        $topic = new AmqpMessage('aName');

        $topic->setMandatory(false);
        $this->assertFalse($topic->isMandatory());

        $topic->setMandatory(true);
        $this->assertTrue($topic->isMandatory());
    }

    public function testShouldAllowGetPreviouslySetImmediate()
    {
        $topic = new AmqpMessage('aName');

        $topic->setImmediate(false);
        $this->assertFalse($topic->isImmediate());

        $topic->setImmediate(true);
        $this->assertTrue($topic->isImmediate());
    }

    public function testShouldAllowGetPreviouslySetTicket()
    {
        $topic = new AmqpMessage('aName');

        //guard
        $this->assertSame(null, $topic->getTicket());

        $topic->setTicket('ticket');

        $this->assertSame('ticket', $topic->getTicket());
    }
}
