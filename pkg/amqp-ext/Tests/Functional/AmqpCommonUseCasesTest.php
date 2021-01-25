<?php

namespace Enqueue\AmqpExt\Tests\Functional;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Test\RabbitManagementExtensionTrait;
use Enqueue\Test\RabbitmqAmqpExtension;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class AmqpCommonUseCasesTest extends TestCase
{
    use RabbitManagementExtensionTrait;
    use RabbitmqAmqpExtension;

    /**
     * @var AmqpContext
     */
    private $amqpContext;

    protected function setUp(): void
    {
        $this->amqpContext = $this->buildAmqpContext();

        $this->removeQueue('amqp_ext.test');
        $this->removeExchange('amqp_ext.test_exchange');
    }

    protected function tearDown(): void
    {
        $this->amqpContext->close();
    }

    public function testWaitsForTwoSecondsAndReturnNullOnReceive()
    {
        $queue = $this->amqpContext->createQueue('amqp_ext.test');
        $this->amqpContext->declareQueue($queue);

        $startAt = microtime(true);

        $consumer = $this->amqpContext->createConsumer($queue);
        $message = $consumer->receive(2000);

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertGreaterThan(1.5, $endAt - $startAt);
        $this->assertLessThan(2.5, $endAt - $startAt);
    }

    public function testReturnNullImmediatelyOnReceiveNoWait()
    {
        $queue = $this->amqpContext->createQueue('amqp_ext.test');
        $this->amqpContext->declareQueue($queue);

        $startAt = microtime(true);

        $consumer = $this->amqpContext->createConsumer($queue);
        $message = $consumer->receiveNoWait();

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertLessThan(0.5, $endAt - $startAt);
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToQueue()
    {
        $queue = $this->amqpContext->createQueue('amqp_ext.test');
        $this->amqpContext->declareQueue($queue);

        $message = $this->amqpContext->createMessage(
            __METHOD__,
            ['FooProperty' => 'FooVal'],
            ['BarHeader' => 'BarVal']
        );

        $producer = $this->amqpContext->createProducer();
        $producer->send($queue, $message);

        $consumer = $this->amqpContext->createConsumer($queue);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
        $this->assertEquals(['FooProperty' => 'FooVal'], $message->getProperties());
        $this->assertEquals([
            'message_id' => '',
            'correlation_id' => '',
            'app_id' => '',
            'type' => '',
            'content_encoding' => '',
            'content_type' => 'text/plain',
            'expiration' => '',
            'priority' => '0',
            'reply_to' => '',
            'timestamp' => '0',
            'user_id' => '',
        ], $message->getHeaders());
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToTemporaryQueue()
    {
        $queue = $this->amqpContext->createTemporaryQueue();

        $message = $this->amqpContext->createMessage(__METHOD__);

        $producer = $this->amqpContext->createProducer();
        $producer->send($queue, $message);

        $consumer = $this->amqpContext->createConsumer($queue);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToTopic()
    {
        $topic = $this->amqpContext->createTopic('amqp_ext.test_exchange');
        $topic->setType(AMQP_EX_TYPE_FANOUT);
        $this->amqpContext->declareTopic($topic);

        $queue = $this->amqpContext->createQueue('amqp_ext.test');
        $this->amqpContext->declareQueue($queue);

        $this->amqpContext->bind(new AmqpBind($topic, $queue));

        $message = $this->amqpContext->createMessage(__METHOD__);

        $producer = $this->amqpContext->createProducer();
        $producer->send($topic, $message);

        $consumer = $this->amqpContext->createConsumer($queue);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testConsumerReceiveMessageFromTopicDirectly()
    {
        $topic = $this->amqpContext->createTopic('amqp_ext.test_exchange');
        $topic->setType(AMQP_EX_TYPE_FANOUT);

        $this->amqpContext->declareTopic($topic);

        $consumer = $this->amqpContext->createConsumer($topic);
        //guard
        $this->assertNull($consumer->receive(1000));

        $message = $this->amqpContext->createMessage(__METHOD__);

        $producer = $this->amqpContext->createProducer();
        $producer->send($topic, $message);
        $actualMessage = $consumer->receive(1000);

        $this->assertInstanceOf(AmqpMessage::class, $actualMessage);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testConsumerReceiveMessageWithZeroTimeout()
    {
        $topic = $this->amqpContext->createTopic('amqp_ext.test_exchange');
        $topic->setType(AMQP_EX_TYPE_FANOUT);

        $this->amqpContext->declareTopic($topic);

        $consumer = $this->amqpContext->createConsumer($topic);
        //guard
        $this->assertNull($consumer->receive(1000));

        $message = $this->amqpContext->createMessage(__METHOD__);

        $producer = $this->amqpContext->createProducer();
        $producer->send($topic, $message);
        usleep(100);
        $actualMessage = $consumer->receive(0);

        $this->assertInstanceOf(AmqpMessage::class, $actualMessage);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testPurgeMessagesFromQueue()
    {
        $queue = $this->amqpContext->createQueue('amqp_ext.test');
        $this->amqpContext->declareQueue($queue);

        $consumer = $this->amqpContext->createConsumer($queue);

        $message = $this->amqpContext->createMessage(__METHOD__);

        $producer = $this->amqpContext->createProducer();
        $producer->send($queue, $message);
        $producer->send($queue, $message);

        $this->amqpContext->purgeQueue($queue);

        $this->assertNull($consumer->receive(1));
    }
}
