<?php

namespace Enqueue\Bundle\Tests\Functional\Events;

use Enqueue\AsyncEventDispatcher\AsyncListener;
use Enqueue\AsyncEventDispatcher\Commands;
use Enqueue\Bundle\Tests\Functional\App\TestAsyncSubscriber;
use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\TraceableProducer;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @group functional
 */
class AsyncSubscriberTest extends WebTestCase
{
    protected function tearDown()
    {
        parent::tearDown();

//<<<<<<< HEAD
//        static::$container = null;
//=======
        /** @var AsyncListener $asyncListener */
        $asyncListener = static::$container->get('enqueue.events.async_listener');

        $asyncListener->resetSyncMode();
        static::$container->get('test_async_subscriber')->calls = [];
        static::$container->get('test_async_listener')->calls = [];
//>>>>>>> origin/master
    }

    public function testShouldNotCallRealSubscriberIfMarkedAsAsync()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $dispatcher->dispatch('test_async_subscriber', new GenericEvent('aSubject'));

        /** @var TestAsyncSubscriber $listener */
        $listener = static::$container->get('test_async_subscriber');

        $this->assertEmpty($listener->calls);
    }

    public function testShouldSendMessageToExpectedTopicInsteadOfCallingRealSubscriber()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $event = new GenericEvent('theSubject', ['fooArg' => 'fooVal']);

        $dispatcher->dispatch('test_async_subscriber', $event);

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

        $dispatcher->dispatch('test_async_subscriber', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));
        $dispatcher->dispatch('test_async_subscriber', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));
        $dispatcher->dispatch('test_async_subscriber', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));

        /** @var TraceableProducer $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $traces = $producer->getCommandTraces(Commands::DISPATCH_ASYNC_EVENTS);

        $this->assertCount(3, $traces);
    }

    public function testShouldSendMessageIfDispatchedFromInsideListener()
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$container->get('event_dispatcher');

        $eventName = 'anEvent'.uniqid();
        $dispatcher->addListener($eventName, function (Event $event, $eventName, EventDispatcherInterface $dispatcher) {
            $dispatcher->dispatch('test_async_subscriber', new GenericEvent('theSubject', ['fooArg' => 'fooVal']));
        });

        $dispatcher->dispatch($eventName);

        /** @var TraceableProducer $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $traces = $producer->getCommandTraces(Commands::DISPATCH_ASYNC_EVENTS);

        $this->assertCount(1, $traces);
    }
}
