<?php

namespace Enqueue\LaravelQueue\Tests;

use Enqueue\LaravelQueue\Job;
use Enqueue\LaravelQueue\Queue;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Queue as BaseQueue;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsQueueContract()
    {
        $this->assertClassExtends(QueueContract::class, Queue::class);
    }

    public function testShouldExtendsBaseQueue()
    {
        $this->assertClassExtends(BaseQueue::class, Queue::class);
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new Queue($this->createPsrContextMock(), 'queueName', 123);
    }

    public function testShouldReturnPsrContextSetInConstructor()
    {
        $psrContext = $this->createPsrContextMock();

        $queue = new Queue($psrContext, 'queueName', 123);

        $this->assertSame($psrContext, $queue->getPsrContext());
    }

    public function testShouldReturnTimeToRunSetInConstructor()
    {
        $psrContext = $this->createPsrContextMock();

        $queue = new Queue($psrContext, 'queueName', 123);

        $this->assertSame(123, $queue->getTimeToRun());
    }

    public function testShouldReturnDefaultQueueIfNotNameProvided()
    {
        $psrQueue = new NullQueue('queueName');

        $psrContext = $this->createPsrContextMock();
        $psrContext
            ->expects($this->once())
            ->method('createQueue')
            ->with('queueName')
            ->willReturn($psrQueue)
        ;

        $queue = new Queue($psrContext, 'queueName', 123);

        $this->assertSame($psrQueue, $queue->getQueue());
    }

    public function testShouldReturnCustomQueueIfNameProvided()
    {
        $psrQueue = new NullQueue('theCustomQueueName');

        $psrContext = $this->createPsrContextMock();
        $psrContext
            ->expects($this->once())
            ->method('createQueue')
            ->with('theCustomQueueName')
            ->willReturn($psrQueue)
        ;

        $queue = new Queue($psrContext, 'queueName', 123);

        $this->assertSame($psrQueue, $queue->getQueue('theCustomQueueName'));
    }

    public function testShouldSendJobAsMessageToExpectedQueue()
    {
        $psrQueue = new NullQueue('theCustomQueueName');

        $psrProducer = $this->createMock(PsrProducer::class);
        $psrProducer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (PsrQueue $queue, PsrMessage $message) {
                $this->assertSame('theCustomQueueName', $queue->getQueueName());

                $this->assertContains('"displayName":"Enqueue\\\LaravelQueue\\\Tests\\\TestJob"', $message->getBody());
                $this->assertSame([], $message->getProperties());
                $this->assertSame([], $message->getHeaders());
            })
        ;

        $psrContext = $this->createPsrContextMock();
        $psrContext
            ->expects($this->once())
            ->method('createQueue')
            ->with('theCustomQueueName')
            ->willReturn($psrQueue)
        ;
        $psrContext
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($psrProducer)
        ;
        $psrContext
            ->expects($this->once())
            ->method('createMessage')
            ->willReturnCallback(function ($body, $properties, $headers) {
                return new NullMessage($body, $properties, $headers);
            })
        ;

        $queue = new Queue($psrContext, 'queueName', 123);

        $queue->push(new TestJob(), '', 'theCustomQueueName');
    }

    public function testShouldSendDoRawPush()
    {
        $psrQueue = new NullQueue('theCustomQueueName');

        $psrProducer = $this->createMock(PsrProducer::class);
        $psrProducer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (PsrQueue $queue, PsrMessage $message) {
                $this->assertSame('theCustomQueueName', $queue->getQueueName());

                $this->assertSame('thePayload', $message->getBody());
                $this->assertSame([], $message->getProperties());
                $this->assertSame([], $message->getHeaders());
            })
        ;

        $psrContext = $this->createPsrContextMock();
        $psrContext
            ->expects($this->once())
            ->method('createQueue')
            ->with('theCustomQueueName')
            ->willReturn($psrQueue)
        ;
        $psrContext
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($psrProducer)
        ;
        $psrContext
            ->expects($this->once())
            ->method('createMessage')
            ->willReturnCallback(function ($body, $properties, $headers) {
                return new NullMessage($body, $properties, $headers);
            })
        ;

        $queue = new Queue($psrContext, 'queueName', 123);

        $queue->pushRaw('thePayload', 'theCustomQueueName');
    }

    public function testShouldReturnNullIfNoMessageInQueue()
    {
        $psrQueue = new NullQueue('theCustomQueueName');

        $psrConsumer = $this->createMock(PsrConsumer::class);
        $psrConsumer
            ->expects($this->once())
            ->method('receive')
            ->with(1000)
            ->willReturn(null)
        ;

        $psrContext = $this->createPsrContextMock();
        $psrContext
            ->expects($this->once())
            ->method('createQueue')
            ->with('theCustomQueueName')
            ->willReturn($psrQueue)
        ;
        $psrContext
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($psrQueue))
            ->willReturn($psrConsumer)
        ;

        $queue = new Queue($psrContext, 'queueName', 123);

        $this->assertNull($queue->pop('theCustomQueueName'));
    }

    public function testShouldReturnJobForReceivedMessage()
    {
        $psrQueue = new NullQueue('theCustomQueueName');
        $psrMessage = new NullMessage();

        $psrConsumer = $this->createMock(PsrConsumer::class);
        $psrConsumer
            ->expects($this->once())
            ->method('receive')
            ->with(1000)
            ->willReturn($psrMessage)
        ;

        $psrContext = $this->createPsrContextMock();
        $psrContext
            ->expects($this->once())
            ->method('createQueue')
            ->with('theCustomQueueName')
            ->willReturn($psrQueue)
        ;
        $psrContext
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($psrQueue))
            ->willReturn($psrConsumer)
        ;

        $queue = new Queue($psrContext, 'queueName', 123);
        $queue->setContainer(new Container());

        $job = $queue->pop('theCustomQueueName');

        $this->assertInstanceOf(Job::class, $job);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }
}

class TestJob implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle()
    {
    }
}
