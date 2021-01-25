<?php

declare(strict_types=1);

namespace Enqueue\Dbal\Tests\Functional;

use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\Tests\Spec\Mysql\CreateDbalContextTrait;
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

    protected function setUp(): void
    {
        $this->context = $this->createDbalContext();
    }

    protected function tearDown(): void
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
        $this->assertSame(0, $this->getQuerySize());

        $time = (int) (microtime(true) * 10000);

        $expectedBody = __CLASS__.$time;

        $producer = $context->createProducer();

        /** @var DbalMessage $message */
        $message = $context->createMessage($expectedBody);
        $message->setPublishedAt($time);
        $producer->send($queue, $message);

        $message = $consumer->receive(100); // 100ms

        $this->assertInstanceOf(DbalMessage::class, $message);
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
        $this->assertSame(0, $this->getQuerySize());

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

        $this->assertInstanceOf(DbalMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedPriority5BodyOlderTime, $message->getBody());

        $message = $consumer->receive(100); // 8 sec

        $this->assertInstanceOf(DbalMessage::class, $message);
        $consumer->acknowledge($message);
        $this->assertSame($expectedPriority5Body, $message->getBody());
    }

    public function testShouldDeleteExpiredMessage()
    {
        $context = $this->context;
        $queue = $context->createQueue(__METHOD__);

        // guard
        $this->assertSame(0, $this->getQuerySize());

        $producer = $context->createProducer();

        $this->context->getDbalConnection()->insert(
            $this->context->getTableName(), [
            'id' => 'id',
            'published_at' => '123',
            'body' => 'expiredMessage',
            'headers' => json_encode([]),
            'properties' => json_encode([]),
            'queue' => __METHOD__,
            'redelivered' => 0,
            'time_to_live' => time() - 10000,
        ]);

        $message = $context->createMessage('notExpiredMessage');
        $message->setRedelivered(false);
        $producer->send($queue, $message);

        $this->assertSame(2, $this->getQuerySize());

        // we need a new consumer to workaround redeliver
        $consumer = $context->createConsumer($queue);
        $message = $consumer->receive(100);

        $this->assertSame(1, $this->getQuerySize());

        $consumer->acknowledge($message);

        $this->assertSame(0, $this->getQuerySize());
    }

    public function testShouldRemoveOriginalMessageThatHaveBeenRejectedWithRequeue()
    {
        $context = $this->context;
        $queue = $context->createQueue(__METHOD__);

        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertSame(0, $this->getQuerySize());

        $producer = $context->createProducer();

        /** @var DbalMessage $message */
        $message = $context->createMessage(__CLASS__);
        $producer->send($queue, $message);

        $this->assertSame(1, $this->getQuerySize());

        $message = $consumer->receive(100); // 100ms

        $this->assertInstanceOf(DbalMessage::class, $message);
        $consumer->reject($message, true);
        $this->assertSame(1, $this->getQuerySize());
    }

    private function getQuerySize(): int
    {
        return (int) $this->context->getDbalConnection()
            ->executeQuery('SELECT count(*) FROM '.$this->context->getTableName())
            ->fetchColumn(0)
        ;
    }
}
