<?php

namespace Enqueue\Bundle\Tests\Functional\Events;

use Enqueue\Bundle\Events\AsyncListener;
use Enqueue\Bundle\Events\AsyncProcessor;
use Enqueue\Bundle\Tests\Functional\App\TestAsyncListener;
use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Psr\PsrProcessor;
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

        $event = new GenericEvent('theSubject', ['fooArg' => 'fooVal']);

        $message = new NullMessage();
        $message->setProperty('event_name', 'test_async');
        $message->setProperty('transformer_name', 'php_serializer');
        $message->setBody(serialize($event));

        $this->assertEquals(PsrProcessor::ACK, $processor->process($message, new NullContext()));

        /** @var TestAsyncListener $listener */
        $listener = $this->container->get('test_async_listener');

        $this->assertNotEmpty($listener->calls);

        $this->assertEquals($event, $listener->calls[0][0]);
        $this->assertEquals('test_async', $listener->calls[0][1]);

        $this->assertSame(
            $this->container->get('enqueue.events.event_dispatcher'),
            $listener->calls[0][2]
        );
    }
}
