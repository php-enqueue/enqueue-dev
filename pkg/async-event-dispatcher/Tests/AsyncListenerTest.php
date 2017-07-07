<?php

namespace Enqueue\AsyncEventDispatcher\Tests;

use Enqueue\AsyncEventDispatcher\AsyncListener;
use Enqueue\AsyncEventDispatcher\EventTransformer;
use Enqueue\AsyncEventDispatcher\Registry;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProducer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

class AsyncListenerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithContextAndRegistryAndEventQueueAsString()
    {
        $eventQueue = new NullQueue('symfony_events');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('symfony_events')
            ->willReturn($eventQueue)
        ;

        $listener = new AsyncListener($context, $this->createRegistryMock(), 'symfony_events');

        $this->assertAttributeSame($eventQueue, 'eventQueue', $listener);
    }

    public function testCouldBeConstructedWithContextAndRegistryAndPsrQueue()
    {
        $eventQueue = new NullQueue('symfony_events');

        $context = $this->createContextMock();
        $context
            ->expects($this->never())
            ->method('createQueue')
        ;

        $listener = new AsyncListener($context, $this->createRegistryMock(), $eventQueue);

        $this->assertAttributeSame($eventQueue, 'eventQueue', $listener);
    }

    public function testShouldDoNothingIfSyncModeOn()
    {
        $producer = $this->createContextMock();
        $producer
            ->expects($this->never())
            ->method('createProducer')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->never())
            ->method('getTransformerNameForEvent')
        ;

        $listener = new AsyncListener($producer, $registry, new NullQueue('symfony_events'));

        $listener->syncMode('fooEvent');

        $listener->onEvent(new Event(), 'fooEvent');
        $listener->onEvent(new GenericEvent(), 'fooEvent');
    }

    public function testShouldSendMessageIfSyncModeOff()
    {
        $event = new GenericEvent();

        $message = new NullMessage('serializedEvent');
        $eventQueue = new NullQueue('symfony_events');

        $transformerMock = $this->createEventTransformerMock();
        $transformerMock
            ->expects($this->once())
            ->method('toMessage')
            ->with('fooEvent', $this->identicalTo($event))
            ->willReturn($message)
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getTransformerNameForEvent')
            ->with('fooEvent')
            ->willReturn('fooTrans')
        ;
        $registry
            ->expects($this->once())
            ->method('getTransformer')
            ->with('fooTrans')
            ->willReturn($transformerMock)
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($eventQueue), $this->identicalTo($message))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->with()
            ->willReturn($producer)
        ;

        $listener = new AsyncListener($context, $registry, $eventQueue);

        $listener->onEvent($event, 'fooEvent');

        $this->assertEquals('serializedEvent', $message->getBody());
        $this->assertEquals([], $message->getHeaders());
        $this->assertEquals([
            'event_name' => 'fooEvent',
            'transformer_name' => 'fooTrans',
        ], $message->getProperties());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventTransformer
     */
    private function createEventTransformerMock()
    {
        return $this->createMock(EventTransformer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(PsrProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    private function createRegistryMock()
    {
        return $this->createMock(Registry::class);
    }
}
