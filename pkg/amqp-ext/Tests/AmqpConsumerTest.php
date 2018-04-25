<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConsumer;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\Buffer;
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

    public function testCouldBeConstructedWithContextAndQueueAndBufferAsArguments()
    {
        new AmqpConsumer(
            $this->createContext(),
            new AmqpQueue('aName'),
            new Buffer(),
            'basic_get'
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
