<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Interop\Queue\Message;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group rdkafka
 * @group functional
 * @retry 5
 */
class RdKafkaSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    public function test()
    {
        $context = $this->createContext();

        $topic = $this->createTopic($context, uniqid('', true));

        $expectedBody = __CLASS__.time();
        $producer = $context->createProducer();
        $producer->send($topic, $context->createMessage($expectedBody));

        // Calling close causes Producer to flush (wait for messages to be delivered to Kafka)
        $context->close();

        $consumer = $context->createConsumer($topic);

        $context->createProducer()->send($topic, $context->createMessage($expectedBody));

        $message = $consumer->receive(10000); // 10 sec

        $this->assertInstanceOf(Message::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }

    protected function createContext()
    {
        $config = [
            'global' => [
                'group.id' => uniqid('', true),
                'metadata.broker.list' => getenv('RDKAFKA_HOST').':'.getenv('RDKAFKA_PORT'),
                'enable.auto.commit' => 'false',
            ],
            'topic' => [
                'auto.offset.reset' => 'beginning',
            ],
        ];

        $context = (new RdKafkaConnectionFactory($config))->createContext();

        return $context;
    }
}
