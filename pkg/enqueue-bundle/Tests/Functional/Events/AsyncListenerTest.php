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
    public function setUp()
    {
        $this->markTestSkipped('Configuration for async_events is not yet ready');

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

        $dispatcher->dispatch('test_async', new GenericEvent('aSubject'));

        /** @var TestAsyncListener $listener */
        $listener = static::$container->get('test_async_listener');

        $this->assertEmpty($listener->calls);
    }

    public function testShouldSendMessageToExpectedCommandInsteadOfCallingRealListener()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $event = new GenericEvent('theSubject', ['fooArg' => 'fooVal']);

        $dispatcher->dispatch('test_async', $event);

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

        $dispatcher->dispatch('test_async', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));
        $dispatcher->dispatch('test_async', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));
        $dispatcher->dispatch('test_async', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));

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
        $dispatcher->addListener($eventName, function (Event $event, $eventName, EventDispatcherInterface $dispatcher) {
            $dispatcher->dispatch('test_async', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));
        });

        $dispatcher->dispatch($eventName);

        /** @var TraceableProducer $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $traces = $producer->getCommandTraces(Commands::DISPATCH_ASYNC_EVENTS);

        $this->assertCount(1, $traces);
    }
}
