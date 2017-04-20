<?php

namespace Enqueue\Tests\Transport\Null;

use Enqueue\Psr\PsrContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullConsumer;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;
use Enqueue\Transport\Null\NullProducer;
use Enqueue\Transport\Null\NullQueue;
use Enqueue\Transport\Null\NullTopic;
use PHPUnit\Framework\TestCase;

class NullContextTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSessionInterface()
    {
        $this->assertClassImplements(PsrContext::class, NullContext::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullContext();
    }

    public function testShouldAllowCreateMessageWithoutAnyArguments()
    {
        $context = new NullContext();

        $message = $context->createMessage();

        $this->assertInstanceOf(NullMessage::class, $message);

        $this->assertNull($message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $context = new NullContext();

        $message = $context->createMessage('theBody', ['theProperty'], ['theHeader']);

        $this->assertInstanceOf(NullMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['theProperty'], $message->getProperties());
        $this->assertSame(['theHeader'], $message->getHeaders());
    }

    public function testShouldAllowCreateQueue()
    {
        $context = new NullContext();

        $queue = $context->createQueue('aName');

        $this->assertInstanceOf(NullQueue::class, $queue);
    }

    public function testShouldAllowCreateTopic()
    {
        $context = new NullContext();

        $topic = $context->createTopic('aName');

        $this->assertInstanceOf(NullTopic::class, $topic);
    }

    public function testShouldAllowCreateConsumerForGivenQueue()
    {
        $context = new NullContext();

        $queue = new NullQueue('aName');

        $consumer = $context->createConsumer($queue);

        $this->assertInstanceOf(NullConsumer::class, $consumer);
    }

    public function testShouldAllowCreateProducer()
    {
        $context = new NullContext();

        $producer = $context->createProducer();

        $this->assertInstanceOf(NullProducer::class, $producer);
    }

    public function testShouldCreateTempraryQueueWithUnqiueName()
    {
        $context = new NullContext();

        $firstTmpQueue = $context->createTemporaryQueue();
        $secondTmpQueue = $context->createTemporaryQueue();

        $this->assertInstanceOf(NullQueue::class, $firstTmpQueue);
        $this->assertInstanceOf(NullQueue::class, $secondTmpQueue);

        $this->assertNotEquals($firstTmpQueue->getQueueName(), $secondTmpQueue->getQueueName());
    }
}
