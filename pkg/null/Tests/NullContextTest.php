<?php

namespace Enqueue\NullTransporter\Tests;

use Enqueue\NullTransporter\NullConsumer;
use Enqueue\NullTransporter\NullContext;
use Enqueue\NullTransporter\NullMessage;
use Enqueue\NullTransporter\NullProducer;
use Enqueue\NullTransporter\NullQueue;
use Enqueue\NullTransporter\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use PHPUnit\Framework\TestCase;

class NullContextTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSessionInterface()
    {
        $this->assertClassImplements(Context::class, NullContext::class);
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

        $this->assertSame('', $message->getBody());
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

    public function testShouldCreateTemporaryQueueWithUniqueName()
    {
        $context = new NullContext();

        $firstTmpQueue = $context->createTemporaryQueue();
        $secondTmpQueue = $context->createTemporaryQueue();

        $this->assertInstanceOf(NullQueue::class, $firstTmpQueue);
        $this->assertInstanceOf(NullQueue::class, $secondTmpQueue);

        $this->assertNotEquals($firstTmpQueue->getQueueName(), $secondTmpQueue->getQueueName());
    }
}
