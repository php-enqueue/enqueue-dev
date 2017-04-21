<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConsumer;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\AmqpQueue;
use Enqueue\AmqpExt\Buffer;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class AmqpConsumerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, AmqpConsumer::class);
    }

    public function testCouldBeConstructedWithContextAndQueueAndBufferAsArguments()
    {
        new AmqpConsumer(
            $this->createContext(),
            new AmqpQueue('aName'),
            new Buffer()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpContext
     */
    private function createContext()
    {
        return $this->createMock(AmqpContext::class);
    }
}
