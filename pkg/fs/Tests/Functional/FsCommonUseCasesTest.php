<?php

namespace Enqueue\Fs\Tests\Functional;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsMessage;
use Makasim\File\TempFile;

/**
 * @group functional
 */
class FsCommonUseCasesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FsContext
     */
    private $fsContext;

    protected function setUp(): void
    {
        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir()]))->createContext();

        new TempFile(sys_get_temp_dir().'/fs_test_queue');
    }

    protected function tearDown(): void
    {
        $this->fsContext->close();
    }

    public function testWaitsForTwoSecondsAndReturnNullOnReceive()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $startAt = microtime(true);

        $consumer = $this->fsContext->createConsumer($queue);
        $message = $consumer->receive(2000);

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertGreaterThan(1.5, $endAt - $startAt);
        $this->assertLessThan(2.5, $endAt - $startAt);
    }

    public function testReturnNullImmediatelyOnReceiveNoWait()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $startAt = microtime(true);

        $consumer = $this->fsContext->createConsumer($queue);
        $message = $consumer->receiveNoWait();

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertLessThan(0.5, $endAt - $startAt);
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToQueue()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $message = $this->fsContext->createMessage(
            __METHOD__,
            ['FooProperty' => 'FooVal'],
            ['BarHeader' => 'BarVal']
        );

        $producer = $this->fsContext->createProducer();
        $producer->send($queue, $message);

        $consumer = $this->fsContext->createConsumer($queue);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(FsMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
        $this->assertEquals(['FooProperty' => 'FooVal'], $message->getProperties());
        $this->assertEquals([
            'BarHeader' => 'BarVal',
        ], $message->getHeaders());
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToTemporaryQueue()
    {
        $queue = $this->fsContext->createTemporaryQueue();

        $message = $this->fsContext->createMessage(__METHOD__);

        $producer = $this->fsContext->createProducer();
        $producer->send($queue, $message);

        $consumer = $this->fsContext->createConsumer($queue);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(FsMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testConsumerReceiveMessageWithZeroTimeout()
    {
        $topic = $this->fsContext->createTopic('fs_test_queue_exchange');

        $consumer = $this->fsContext->createConsumer($topic);
        //guard
        $this->assertNull($consumer->receive(1000));

        $message = $this->fsContext->createMessage(__METHOD__);

        $producer = $this->fsContext->createProducer();
        $producer->send($topic, $message);
        usleep(100);
        $actualMessage = $consumer->receive(0);

        $this->assertInstanceOf(FsMessage::class, $actualMessage);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testPurgeMessagesFromQueue()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $consumer = $this->fsContext->createConsumer($queue);

        $message = $this->fsContext->createMessage(__METHOD__);

        $producer = $this->fsContext->createProducer();
        $producer->send($queue, $message);
        $producer->send($queue, $message);

        $this->fsContext->purgeQueue($queue);

        $this->assertNull($consumer->receive(1));
    }
}
