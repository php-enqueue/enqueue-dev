<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\MessagePriority;
use PHPUnit\Framework\TestCase;

class MessagePriorityTest extends TestCase
{
    public function testShouldVeryLowPriorityHasExpectedValue()
    {
        $this->assertSame('enqueue.message_queue.client.very_low_message_priority', MessagePriority::VERY_LOW);
    }

    public function testShouldLowPriorityHasExpectedValue()
    {
        $this->assertSame('enqueue.message_queue.client.low_message_priority', MessagePriority::LOW);
    }

    public function testShouldMediumPriorityHasExpectedValue()
    {
        $this->assertSame('enqueue.message_queue.client.normal_message_priority', MessagePriority::NORMAL);
    }

    public function testShouldHighPriorityHasExpectedValue()
    {
        $this->assertSame('enqueue.message_queue.client.high_message_priority', MessagePriority::HIGH);
    }

    public function testShouldVeryHighPriorityHasExpectedValue()
    {
        $this->assertSame('enqueue.message_queue.client.very_high_message_priority', MessagePriority::VERY_HIGH);
    }
}
