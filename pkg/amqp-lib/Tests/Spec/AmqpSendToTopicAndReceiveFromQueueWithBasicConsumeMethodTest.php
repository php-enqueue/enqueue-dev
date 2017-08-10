<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpContext;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\Spec\SendToTopicAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class AmqpSendToTopicAndReceiveFromQueueWithBasicConsumeMethodTest extends SendToTopicAndReceiveFromQueueSpec
{
    private $topic;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN').'?receive_method=basic_consume');

        return $factory->createContext();
    }

    public function test()
    {
        $context = $this->createContext();
        $topic = $this->createTopic($context, 'send_to_topic_and_receive_from_queue_spec');
        $queue = $this->createQueue($context, 'send_to_topic_and_receive_from_queue_spec');

        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertNull($consumer->receiveNoWait());

        $expectedBody = __CLASS__.time();

        $context->createProducer()->send($topic, $context->createMessage($expectedBody));

        $message = $consumer->receive(10000); // 2 sec

        var_dump($message);

        $this->assertInstanceOf(PsrMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpContext $context
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        $queueName .= '_basic_consume';

        $queue = $context->createQueue($queueName);
        $context->declareQueue($queue);

        $context->bind(new AmqpBind($this->topic, $queue));

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpContext $context
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        $topicName .= '_basic_consume';

        $topic = $context->createTopic($topicName);
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $context->declareTopic($topic);

        return $this->topic = $topic;
    }
}
