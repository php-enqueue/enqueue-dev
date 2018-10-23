<?php

namespace Enqueue\Dbal\Tests\Functional;

use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\Tests\Spec\CreateDbalContextTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class DbalConsumerTest extends TestCase
{
    use CreateDbalContextTrait;

    /**
     * @var DbalContext
     */
    private $context;

    public function setUp()
    {
        $this->context = $this->createDbalContext();
    }

    protected function tearDown()
    {
        if ($this->context) {
            $this->context->close();
        }

        parent::tearDown();
    }

    public function testShouldSetPublishedAtDateToReceivedMessage()
    {
        $context = $this->context;
        $queue = $context->createQueue(__METHOD__);

        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertNull($consumer->receiveNoWait());

        $time = (int) (microtime(true) * 10000);

        $expectedBody = __CLASS__.$time;

        $producer = $context->createProducer();

        /** @var DbalMessage $message */
        $message = $context->createMessage($expectedBody);
        $message->setPublishedAt($time);
        $producer->send($queue, $message);

        $message = $consumer->receive(8000); // 8 sec

        $this->assertInstanceOf(DbalMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedBody, $message->getBody());
        $this->assertSame($time, $message->getPublishedAt());
    }

    public function testShouldOrderMessagesWithSamePriorityByPublishedAtDateee()
    {
        $context = $this->context;
        $queue = $context->createQueue(__METHOD__);

        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertNull($consumer->receiveNoWait());

        $time = (int) (microtime(true) * 10000);
        $olderTime = $time - 10000;

        $expectedPriority5Body = __CLASS__.'_priority5_'.$time;
        $expectedPriority5BodyOlderTime = __CLASS__.'_priority5_'.$olderTime;

        $producer = $context->createProducer();

        /** @var DbalMessage $message */
        $message = $context->createMessage($expectedPriority5Body);
        $message->setPriority(5);
        $message->setPublishedAt($time);
        $producer->send($queue, $message);

        $message = $context->createMessage($expectedPriority5BodyOlderTime);
        $message->setPriority(5);
        $message->setPublishedAt($olderTime);
        $producer->send($queue, $message);

        $message = $consumer->receive(8000); // 8 sec

        $this->assertInstanceOf(DbalMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedPriority5BodyOlderTime, $message->getBody());

        $message = $consumer->receive(8000); // 8 sec

        $this->assertInstanceOf(DbalMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedPriority5Body, $message->getBody());
    }
}
