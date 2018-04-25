<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\Buffer;
use Interop\Amqp\Impl\AmqpMessage;
use PHPUnit\Framework\TestCase;

class BufferTest extends TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new Buffer();
    }

    public function testShouldSetEmptyArrayToMessagesPropertyOnConstruct()
    {
        $buffer = new Buffer();

        $this->assertAttributeSame([], 'messages', $buffer);
    }

    public function testShouldReturnNullIfNoMessagesInBuffer()
    {
        $buffer = new Buffer();

        $this->assertNull($buffer->pop('aConsumerTag'));
        $this->assertNull($buffer->pop('anotherConsumerTag'));
    }

    public function testShouldPushMessageToBuffer()
    {
        $fooMessage = new AmqpMessage();
        $barMessage = new AmqpMessage();
        $bazMessage = new AmqpMessage();

        $buffer = new Buffer();

        $buffer->push('aConsumerTag', $fooMessage);
        $buffer->push('aConsumerTag', $barMessage);

        $buffer->push('anotherConsumerTag', $bazMessage);

        $this->assertAttributeSame([
            'aConsumerTag' => [$fooMessage, $barMessage],
            'anotherConsumerTag' => [$bazMessage],
        ], 'messages', $buffer);
    }

    public function testShouldPopMessageFromBuffer()
    {
        $fooMessage = new AmqpMessage();
        $barMessage = new AmqpMessage();

        $buffer = new Buffer();

        $buffer->push('aConsumerTag', $fooMessage);
        $buffer->push('aConsumerTag', $barMessage);

        $this->assertSame($fooMessage, $buffer->pop('aConsumerTag'));
        $this->assertSame($barMessage, $buffer->pop('aConsumerTag'));
        $this->assertNull($buffer->pop('aConsumerTag'));
    }
}
