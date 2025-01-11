<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConsumer;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Queue\Consumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpConsumerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, AmqpConsumer::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithContextAndQueueAsArguments()
    {
        new AmqpConsumer($this->createContext(), new AmqpQueue('aName'));
    }

    /**
     * @return MockObject|AmqpContext
     */
    private function createContext()
    {
        return $this->createMock(AmqpContext::class);
    }
}
