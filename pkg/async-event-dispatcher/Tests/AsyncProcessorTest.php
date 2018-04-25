<?php

namespace Enqueue\AsyncEventDispatcher\Tests;

use Enqueue\AsyncEventDispatcher\AsyncEventDispatcher;
use Enqueue\AsyncEventDispatcher\AsyncProcessor;
use Enqueue\AsyncEventDispatcher\EventTransformer;
use Enqueue\AsyncEventDispatcher\Registry;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class AsyncProcessorTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorInterface()
    {
        $this->assertClassImplements(PsrProcessor::class, AsyncProcessor::class);
    }

    public function testCouldBeConstructedWithRegistryAndProxyEventDispatcher()
    {
        new AsyncProcessor($this->createRegistryMock(), $this->createProxyEventDispatcherMock());
    }

    public function testRejectIfMessageMissingEventNameProperty()
    {
        $processor = new AsyncProcessor($this->createRegistryMock(), $this->createProxyEventDispatcherMock());

        $result = $processor->process(new NullMessage(), new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals('The message is missing "event_name" property', $result->getReason());
    }

    public function testRejectIfMessageMissingTransformerNameProperty()
    {
        $processor = new AsyncProcessor($this->createRegistryMock(), $this->createProxyEventDispatcherMock());

        $message = new NullMessage();
        $message->setProperty('event_name', 'anEventName');

        $result = $processor->process($message, new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals('The message is missing "transformer_name" property', $result->getReason());
    }

    public function testShouldDispatchAsyncListenersOnly()
    {
        $eventName = 'theEventName';
        $transformerName = 'theTransformerName';

        $event = new GenericEvent();

        $message = new NullMessage();
        $message->setProperty('event_name', $eventName);
        $message->setProperty('transformer_name', $transformerName);

        $transformerMock = $this->createEventTransformerMock();
        $transformerMock
            ->expects($this->once())
            ->method('toEvent')
            ->with($eventName, $this->identicalTo($message))
            ->willReturn($event)
        ;

        $registryMock = $this->createRegistryMock();
        $registryMock
            ->expects($this->once())
            ->method('getTransformer')
            ->with($transformerName)
            ->willReturn($transformerMock)
        ;

        $dispatcherMock = $this->createProxyEventDispatcherMock();
        $dispatcherMock
            ->expects($this->once())
            ->method('dispatchAsyncListenersOnly')
            ->with($eventName, $this->identicalTo($event))
        ;
        $dispatcherMock
            ->expects($this->never())
            ->method('dispatch')
        ;

        $processor = new AsyncProcessor($registryMock, $dispatcherMock);

        $this->assertSame(Result::ACK, $processor->process($message, new NullContext()));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventTransformer
     */
    private function createEventTransformerMock()
    {
        return $this->createMock(EventTransformer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AsyncEventDispatcher
     */
    private function createProxyEventDispatcherMock()
    {
        return $this->createMock(AsyncEventDispatcher::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    private function createRegistryMock()
    {
        return $this->createMock(Registry::class);
    }
}
