<?php

namespace Enqueue\Tests\Router;

use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Router\Recipient;
use Enqueue\Router\RecipientListRouterInterface;
use Enqueue\Router\RouteRecipientListProcessor;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Interop\Queue\Producer as InteropProducer;
use PHPUnit\Framework\TestCase;

class RouteRecipientListProcessorTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorInterface()
    {
        $this->assertClassImplements(Processor::class, RouteRecipientListProcessor::class);
    }

    public function testShouldProduceRecipientsMessagesAndAckOriginalMessage()
    {
        $fooRecipient = new Recipient(new NullQueue('aName'), new NullMessage());
        $barRecipient = new Recipient(new NullQueue('aName'), new NullMessage());

        $originalMessage = new NullMessage();

        $routerMock = $this->createRecipientListRouterMock();
        $routerMock
            ->expects($this->once())
            ->method('route')
            ->with($this->identicalTo($originalMessage))
            ->willReturn([$fooRecipient, $barRecipient])
        ;

        $invoked = $this->exactly(2);
        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($invoked)
            ->method('send')
            ->willReturnCallback(function (Destination $destination, Message $message) use ($invoked, $fooRecipient, $barRecipient) {
                match ($invoked->getInvocationCount()) {
                    1 => $this->assertSame([$fooRecipient->getDestination(), $fooRecipient->getMessage()], [$destination, $message]),
                    2 => $this->assertSame([$barRecipient->getDestination(), $barRecipient->getMessage()], [$destination, $message]),
                };
            });

        $sessionMock = $this->createContextMock();
        $sessionMock
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producerMock)
        ;

        $processor = new RouteRecipientListProcessor($routerMock);

        $status = $processor->process($originalMessage, $sessionMock);

        $this->assertEquals(Result::ACK, $status);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|InteropProducer
     */
    protected function createProducerMock()
    {
        return $this->createMock(InteropProducer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Context
     */
    protected function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RecipientListRouterInterface
     */
    protected function createRecipientListRouterMock()
    {
        return $this->createMock(RecipientListRouterInterface::class);
    }
}
