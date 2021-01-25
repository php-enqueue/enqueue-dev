<?php

namespace Enqueue\AsyncEventDispatcher\Tests\Functional;

use Enqueue\AsyncEventDispatcher\AsyncEventDispatcher;
use Enqueue\AsyncEventDispatcher\AsyncListener;
use Enqueue\AsyncEventDispatcher\AsyncProcessor;
use Enqueue\AsyncEventDispatcher\SimpleRegistry;
use Enqueue\Bundle\Tests\Functional\App\TestAsyncEventTransformer;
use Enqueue\Fs\FsConnectionFactory;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Interop\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group functional
 */
class UseCasesTest extends TestCase
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var AsyncEventDispatcher
     */
    protected $asyncDispatcher;

    /**
     * @var callable
     */
    protected $asyncListener;

    /**
     * @var AsyncProcessor
     */
    protected $asyncProcessor;

    protected function setUp(): void
    {
        (new Filesystem())->remove(__DIR__.'/queues/');

        // it could be any other queue-interop/queue-interop compatible context.
        $this->context = $context = (new FsConnectionFactory('file://'.__DIR__.'/queues'))->createContext();
        $this->queue = $queue = $context->createQueue('symfony_events');

        $registry = new SimpleRegistry(
            [
                'test_async' => 'test_async',
                'test_async_from_async' => 'test_async',
            ],
            [
                'test_async' => new TestAsyncEventTransformer($context),
            ]);

        $asyncListener = new AsyncListener($context, $registry, $queue);
        $this->asyncListener = function ($event, $name, $dispatcher) use ($asyncListener) {
            $asyncListener->onEvent($event, $name);

            $consumer = $this->context->createConsumer($this->queue);

            $message = $consumer->receiveNoWait();

            if ($message) {
                $consumer->reject($message, true);

                echo "Send message for event: $name\n";
            }
        };

        $this->dispatcher = $dispatcher = new EventDispatcher();

        $this->asyncDispatcher = $asyncDispatcher = new AsyncEventDispatcher($dispatcher, $asyncListener);

        $this->asyncProcessor = new AsyncProcessor($registry, $asyncDispatcher);
    }

    public function testShouldDispatchBothAsyncEventAndSyncOne()
    {
        $this->dispatcher->addListener('test_async', function () {
            echo "Sync event\n";
        });

        $this->dispatcher->addListener('test_async', $this->asyncListener);

        $this->asyncDispatcher->addListener('test_async', function ($event, $eventName) {
            echo "Async event\n";
        });

        $this->dispatch($this->dispatcher, new GenericEvent(), 'test_async');
        $this->processMessages();

        $this->expectOutputString("Sync event\nSend message for event: test_async\nAsync event\n");
    }

    public function testShouldDispatchBothAsyncEventAndSyncOneFromWhenDispatchedFromInsideAnotherEvent()
    {
        $this->dispatcher->addListener('foo', function ($event, $name, EventDispatcherInterface $dispatcher) {
            echo "Foo event\n";

            $this->dispatch($dispatcher, new GenericEvent(), 'test_async');
        });

        $this->dispatcher->addListener('test_async', function () {
            echo "Sync event\n";
        });

        $this->dispatcher->addListener('test_async', $this->asyncListener);

        $this->asyncDispatcher->addListener('test_async', function ($event, $eventName) {
            echo "Async event\n";
        });

        $this->dispatch($this->dispatcher, new GenericEvent(), 'foo');

        $this->processMessages();

        $this->expectOutputString("Foo event\nSync event\nSend message for event: test_async\nAsync event\n");
    }

    public function testShouldDispatchOtherAsyncEventFromAsyncEvent()
    {
        $this->dispatcher->addListener('test_async', $this->asyncListener);
        $this->dispatcher->addListener('test_async_from_async', $this->asyncListener);

        $this->asyncDispatcher->addListener('test_async', function ($event, $eventName, EventDispatcherInterface $dispatcher) {
            echo "Async event\n";

            $this->dispatch($dispatcher, new GenericEvent(), 'test_async_from_async');
        });

        $this->dispatcher->addListener('test_async_from_async', function ($event, $eventName, EventDispatcherInterface $dispatcher) {
            echo "Async event from event\n";
        });

        $this->dispatch($this->dispatcher, new GenericEvent(), 'test_async');

        $this->processMessages();
        $this->processMessages();

        $this->expectOutputString("Send message for event: test_async\nAsync event\nSend message for event: test_async_from_async\nAsync event from event\n");
    }

    public function testShouldDispatchSyncListenerIfDispatchedFromAsycListner()
    {
        $this->dispatcher->addListener('test_async', $this->asyncListener);

        $this->dispatcher->addListener('sync', function () {
            echo "Sync event\n";
        });

        $this->asyncDispatcher->addListener('test_async', function ($event, $eventName, EventDispatcherInterface $dispatcher) {
            echo "Async event\n";

            $this->dispatch($dispatcher, new GenericEvent(), 'sync');
        });

        $this->dispatch($this->dispatcher, new GenericEvent(), 'test_async');

        $this->processMessages();

        $this->expectOutputString("Send message for event: test_async\nAsync event\nSync event\n");
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

    private function processMessages()
    {
        $consumer = $this->context->createConsumer($this->queue);
        if ($message = $consumer->receiveNoWait()) {
            $result = $this->asyncProcessor->process($message, $this->context);

            switch ((string) $result) {
                case Processor::ACK:
                    $consumer->acknowledge($message);
                    break;
                case Processor::REJECT:
                    $consumer->reject($message);
                    break;
                case Processor::REQUEUE:
                    $consumer->reject($message, true);
                    break;
                default:
                    throw new \LogicException('Result is not supported');
            }
        }
    }
}
