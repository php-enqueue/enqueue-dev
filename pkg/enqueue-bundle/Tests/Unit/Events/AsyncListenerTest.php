<?php

namespace Enqueue\Bundle\Tests\Unit\Events;

use Enqueue\Bundle\Events\AsyncListener;
use Enqueue\Bundle\Events\EventTransformer;
use Enqueue\Bundle\Events\Registry;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class AsyncListenerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithRegistryAndProxyEventDispatcher()
    {
        new AsyncListener($this->createProducerMock(), $this->createRegistryMock());
    }

    public function testShouldDoNothingIfSyncModeOn()
    {
        $producer = $this->createProducerMock();
        $producer
            ->expects($this->never())
            ->method('sendEvent')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->never())
            ->method('getTransformerNameForEvent')
        ;

        $listener = new AsyncListener($producer, $registry);

        $listener->syncMode('fooEvent');

        $listener->onEvent(null, 'fooEvent');
        $listener->onEvent(new GenericEvent(), 'fooEvent');
    }

    public function testShouldSendMessageIfSyncModeOff()
    {
        $event = new GenericEvent();

        $message = new Message();
        $message->setBody('serializedEvent');

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
            ->method('sendEvent')
            ->with('event.fooEvent', $this->identicalTo($message))
        ;

        $listener = new AsyncListener($producer, $registry);

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
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    private function createRegistryMock()
    {
        return $this->createMock(Registry::class);
    }
}
