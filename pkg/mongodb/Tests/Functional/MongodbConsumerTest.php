<?php

namespace Enqueue\Mongodb\Tests\Functional;

use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbMessage;
use Enqueue\Mongodb\Tests\Spec\CreateMongodbContextTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class MongodbConsumerTest extends TestCase
{
    use CreateMongodbContextTrait;

    /**
     * @var MongodbContext
     */
    private $context;

    public function setUp()
    {
        $this->context = $this->createMongodbContext();
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

        $message = $context->createMessage($expectedBody);
        $message->setPublishedAt($time);
        $producer->send($queue, $message);

        $message = $consumer->receive(8000); // 8 sec

        $this->assertInstanceOf(MongodbMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedBody, $message->getBody());
        $this->assertSame($time, $message->getPublishedAt());
    }

    public function testShouldOrderMessagesWithSamePriorityByPublishedAtDate()
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

        $message = $context->createMessage($expectedPriority5Body);
        $message->setPriority(5);
        $message->setPublishedAt($time);
        $producer->send($queue, $message);

        $message = $context->createMessage($expectedPriority5BodyOlderTime);
        $message->setPriority(5);
        $message->setPublishedAt($olderTime);
        $producer->send($queue, $message);

        $message = $consumer->receive(8000); // 8 sec

        $this->assertInstanceOf(MongodbMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedPriority5BodyOlderTime, $message->getBody());

        $message = $consumer->receive(8000); // 8 sec

        $this->assertInstanceOf(MongodbMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedPriority5Body, $message->getBody());
    }
}
