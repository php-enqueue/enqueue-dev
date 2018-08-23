<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConsumer;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Queue\PsrConsumer;
use PHPUnit\Framework\TestCase;

class AmqpConsumerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, AmqpConsumer::class);
    }

    public function testCouldBeConstructedWithContextAndQueueAsArguments()
    {
        new AmqpConsumer($this->createContext(), new AmqpQueue('aName'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpContext
     */
    private function createContext()
    {
        return $this->createMock(AmqpContext::class);
    }
}
