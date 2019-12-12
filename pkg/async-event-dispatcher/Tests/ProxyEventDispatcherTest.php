<?php

namespace Enqueue\AsyncEventDispatcher\Tests;

use Enqueue\AsyncEventDispatcher\AsyncEventDispatcher;
use Enqueue\AsyncEventDispatcher\AsyncListener;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class ProxyEventDispatcherTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfEventDispatcher()
    {
        $this->assertClassExtends(EventDispatcher::class, AsyncEventDispatcher::class);
    }

    public function testShouldSetSyncModeForGivenEventNameOnDispatchAsyncListenersOnly()
    {
        $asyncListenerMock = $this->createAsyncListenerMock();
        $asyncListenerMock
            ->expects($this->once())
            ->method('resetSyncMode')
        ;
        $asyncListenerMock
            ->expects($this->once())
            ->method('syncMode')
            ->with('theEvent')
        ;

        $trueEventDispatcher = new EventDispatcher();
        $dispatcher = new AsyncEventDispatcher($trueEventDispatcher, $asyncListenerMock);

        $event = new GenericEvent();
        $dispatcher->dispatchAsyncListenersOnly('theEvent', $event);
    }

    public function testShouldCallAsyncEventButNotOtherOnDispatchAsyncListenersOnly()
    {
        $otherEventWasCalled = false;
        $trueEventDispatcher = new EventDispatcher();
        $trueEventDispatcher->addListener('theEvent', function () use (&$otherEventWasCalled) {
            $this->assertInstanceOf(AsyncEventDispatcher::class, func_get_arg(2));

            $otherEventWasCalled = true;
        });

        $asyncEventWasCalled = false;
        $dispatcher = new AsyncEventDispatcher($trueEventDispatcher, $this->createAsyncListenerMock());
        $dispatcher->addListener('theEvent', function () use (&$asyncEventWasCalled) {
            $this->assertInstanceOf(AsyncEventDispatcher::class, func_get_arg(2));

            $asyncEventWasCalled = true;
        });

        $event = new GenericEvent();
        $dispatcher->dispatchAsyncListenersOnly('theEvent', $event);

        $this->assertFalse($otherEventWasCalled);
        $this->assertTrue($asyncEventWasCalled);
    }

    public function testShouldCallOtherEventIfDispatchedFromAsyncEventOnDispatchAsyncListenersOnly()
    {
        $otherEventWasCalled = false;
        $trueEventDispatcher = new EventDispatcher();
        $trueEventDispatcher->addListener('theOtherEvent', function () use (&$otherEventWasCalled) {
            $this->assertNotInstanceOf(AsyncEventDispatcher::class, func_get_arg(2));

            $otherEventWasCalled = true;
        });

        $asyncEventWasCalled = false;
        $dispatcher = new AsyncEventDispatcher($trueEventDispatcher, $this->createAsyncListenerMock());
        $dispatcher->addListener('theEvent', function () use (&$asyncEventWasCalled) {
            $this->assertInstanceOf(AsyncEventDispatcher::class, func_get_arg(2));

            $asyncEventWasCalled = true;

            if (!class_exists(Event::class)) {
                // Symfony 5
                func_get_arg(2)->dispatch(func_get_arg(0), 'theOtherEvent');
            } else {
                // Symfony < 5
                func_get_arg(2)->dispatch('theOtherEvent');
            }
        });

        $event = new GenericEvent();
        $dispatcher->dispatchAsyncListenersOnly('theEvent', $event);

        $this->assertTrue($otherEventWasCalled);
        $this->assertTrue($asyncEventWasCalled);
    }

    public function testShouldNotCallAsyncEventIfDispatchedFromOtherEventOnDispatchAsyncListenersOnly()
    {
        $trueEventDispatcher = new EventDispatcher();
        $trueEventDispatcher->addListener('theOtherEvent', function () {
            func_get_arg(2)->dispatch('theOtherAsyncEvent');
        });

        $dispatcher = new AsyncEventDispatcher($trueEventDispatcher, $this->createAsyncListenerMock());
        $dispatcher->addListener('theAsyncEvent', function () {
            func_get_arg(2)->dispatch('theOtherEvent');
        });
        $asyncEventWasCalled = false;
        $dispatcher->addListener('theOtherAsyncEvent', function () use (&$asyncEventWasCalled) {
            $asyncEventWasCalled = true;
        });

        $event = new GenericEvent();
        $dispatcher->dispatchAsyncListenersOnly('theEvent', $event);

        $this->assertFalse($asyncEventWasCalled);
    }

    /**
     * @return MockObject|AsyncListener
     */
    private function createAsyncListenerMock()
    {
        return $this->createMock(AsyncListener::class);
    }
}
