<?php

namespace Enqueue\Bundle\Tests\Functional\Events;

use Enqueue\AsyncEventDispatcher\AsyncListener;
use Enqueue\AsyncEventDispatcher\AsyncProcessor;
use Enqueue\Bundle\Tests\Functional\App\TestAsyncListener;
use Enqueue\Bundle\Tests\Functional\App\TestAsyncSubscriber;
use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Util\JSON;
use Interop\Queue\PsrProcessor;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @group functional
 */
class AsyncProcessorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        /** @var AsyncListener $asyncListener */
        $asyncListener = $this->container->get('enqueue.events.async_listener');

        $asyncListener->resetSyncMode();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        /** @var AsyncProcessor $processor */
        $processor = $this->container->get('enqueue.events.async_processor');

        $this->assertInstanceOf(AsyncProcessor::class, $processor);
    }

    public function testShouldRejectIfMessageDoesNotContainEventNameProperty()
    {
        /** @var AsyncProcessor $processor */
        $processor = $this->container->get('enqueue.events.async_processor');

        $message = new NullMessage();

        $this->assertEquals(PsrProcessor::REJECT, $processor->process($message, new NullContext()));
    }

    public function testShouldRejectIfMessageDoesNotContainTransformerNameProperty()
    {
        /** @var AsyncProcessor $processor */
        $processor = $this->container->get('enqueue.events.async_processor');

        $message = new NullMessage();
        $message->setProperty('event_name', 'anEventName');

        $this->assertEquals(PsrProcessor::REJECT, $processor->process($message, new NullContext()));
    }

    public function testShouldCallRealListener()
    {
        /** @var AsyncProcessor $processor */
        $processor = $this->container->get('enqueue.events.async_processor');

        $message = new NullMessage();
        $message->setProperty('event_name', 'test_async');
        $message->setProperty('transformer_name', 'test_async');
        $message->setBody(JSON::encode([
            'subject' => 'theSubject',
            'arguments' => ['fooArg' => 'fooVal'],
        ]));

        $this->assertEquals(PsrProcessor::ACK, $processor->process($message, new NullContext()));

        /** @var TestAsyncListener $listener */
        $listener = $this->container->get('test_async_listener');

        $this->assertNotEmpty($listener->calls);

        $this->assertInstanceOf(GenericEvent::class, $listener->calls[0][0]);
        $this->assertEquals('theSubject', $listener->calls[0][0]->getSubject());
        $this->assertEquals(['fooArg' => 'fooVal'], $listener->calls[0][0]->getArguments());
        $this->assertEquals('test_async', $listener->calls[0][1]);

        $this->assertSame(
            $this->container->get('enqueue.events.event_dispatcher'),
            $listener->calls[0][2]
        );
    }

    public function testShouldCallRealSubscriber()
    {
        /** @var AsyncProcessor $processor */
        $processor = $this->container->get('enqueue.events.async_processor');

        $message = new NullMessage();
        $message->setProperty('event_name', 'test_async_subscriber');
        $message->setProperty('transformer_name', 'test_async');
        $message->setBody(JSON::encode([
            'subject' => 'theSubject',
            'arguments' => ['fooArg' => 'fooVal'],
        ]));

        $this->assertEquals(PsrProcessor::ACK, $processor->process($message, new NullContext()));

        /** @var TestAsyncSubscriber $subscriber */
        $subscriber = $this->container->get('test_async_subscriber');

        $this->assertNotEmpty($subscriber->calls);

        $this->assertInstanceOf(GenericEvent::class, $subscriber->calls[0][0]);
        $this->assertEquals('theSubject', $subscriber->calls[0][0]->getSubject());
        $this->assertEquals(['fooArg' => 'fooVal'], $subscriber->calls[0][0]->getArguments());
        $this->assertEquals('test_async_subscriber', $subscriber->calls[0][1]);

        $this->assertSame(
            $this->container->get('enqueue.events.event_dispatcher'),
            $subscriber->calls[0][2]
        );
    }
}
