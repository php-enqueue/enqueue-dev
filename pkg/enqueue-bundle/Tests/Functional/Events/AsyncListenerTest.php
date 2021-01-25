<?php

namespace Enqueue\Bundle\Tests\Functional\Events;

use Enqueue\AsyncEventDispatcher\AsyncListener;
use Enqueue\AsyncEventDispatcher\Commands;
use Enqueue\Bundle\Tests\Functional\App\TestAsyncListener;
use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\TraceableProducer;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @group functional
 */
class AsyncListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var AsyncListener $asyncListener */
        $asyncListener = static::$container->get('enqueue.events.async_listener');

        $asyncListener->resetSyncMode();
        static::$container->get('test_async_subscriber')->calls = [];
        static::$container->get('test_async_listener')->calls = [];
    }

    public function testShouldNotCallRealListenerIfMarkedAsAsync()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $this->dispatch($dispatcher, new GenericEvent('aSubject'), 'test_async');

        /** @var TestAsyncListener $listener */
        $listener = static::$container->get('test_async_listener');

        $this->assertEmpty($listener->calls);
    }

    public function testShouldSendMessageToExpectedCommandInsteadOfCallingRealListener()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $event = new GenericEvent('theSubject', ['fooArg' => 'fooVal']);

        $this->dispatch($dispatcher, $event, 'test_async');

        /** @var TraceableProducer $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $traces = $producer->getCommandTraces(Commands::DISPATCH_ASYNC_EVENTS);

        $this->assertCount(1, $traces);

        $this->assertEquals(Commands::DISPATCH_ASYNC_EVENTS, $traces[0]['command']);
        $this->assertEquals('{"subject":"theSubject","arguments":{"fooArg":"fooVal"}}', $traces[0]['body']);
    }

    public function testShouldSendMessageForEveryDispatchCall()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $this->dispatch($dispatcher, new GenericEvent('theSubject', ['fooArg' => 'fooVal']), 'test_async');
        $this->dispatch($dispatcher, new GenericEvent('theSubject', ['fooArg' => 'fooVal']), 'test_async');
        $this->dispatch($dispatcher, new GenericEvent('theSubject', ['fooArg' => 'fooVal']), 'test_async');

        /** @var TraceableProducer $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $traces = $producer->getCommandTraces(Commands::DISPATCH_ASYNC_EVENTS);

        $this->assertCount(3, $traces);
    }

    public function testShouldSendMessageIfDispatchedFromInsideListener()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $eventName = 'an_event_'.uniqid();
        $dispatcher->addListener($eventName, function ($event, $eventName, EventDispatcherInterface $dispatcher) {
            $this->dispatch($dispatcher, new GenericEvent('theSubject', ['fooArg' => 'fooVal']), 'test_async');
        });

        $this->dispatch($dispatcher, new GenericEvent(), $eventName);

        /** @var TraceableProducer $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $traces = $producer->getCommandTraces(Commands::DISPATCH_ASYNC_EVENTS);

        $this->assertCount(1, $traces);
    }

    private function dispatch(EventDispatcherInterface $dispatcher, $event, $eventName): void
    {
        if (!class_exists(Event::class)) {
            // Symfony 5
            $dispatcher->dispatch($event, $eventName);
        } else {
            // Symfony < 5
            $dispatcher->dispatch($eventName, $event);
        }
    }
}
