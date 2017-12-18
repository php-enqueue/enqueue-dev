<?php

namespace Enqueue\Bench;

use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpMessage;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * @Iterations(5)
 * @OutputTimeUnit("seconds", precision=3)
 */
abstract class EnqueueBaseAmqpBench
{
    /**
     * @var \Interop\Amqp\AmqpContext
     */
    private $context;

    /**
     * @var \Interop\Amqp\AmqpQueue
     */
    private $queue;

    private $bodySize = 10000;

    private $body;

    private $messagesLimit = 10000;

    /**
     * @BeforeMethods({"beforeBenchPublish"})
     */
    public function benchPublish()
    {
        $producer = $this->context->createProducer();

        for ($i = 0; $i < $this->messagesLimit; ++$i) {
            $producer->send($this->queue, $this->context->createMessage($this->body));
        }
    }

//    /**
//     * @BeforeMethods({"beforeBenchConsume"})
//     */
//    public function benchConsume()
//    {
//        $this->context->setQos(0, 3, false);
//
//        $count = 0;
//
//        $callback = function(AmqpMessage $message, AmqpConsumer $consumer) use (&$count) {
//            $count++;
//
//            $consumer->acknowledge($message);
//
//            if ($count >= $this->messagesLimit) {
//                return false;
//            }
//
//            return true;
//        };
//
//        $consumer = $this->context->createConsumer($this->queue);
//        $consumer->setConsumerTag('enqueue_amqp_lib');
//
//        $this->context->subscribe($consumer, $callback);
//        $this->context->consume();
//    }

    public function beforeBenchPublish()
    {
        $bodySize = ((int) getenv('BODY_SIZE'));
        $this->body = str_repeat('a', $bodySize);

        $this->context = $this->createContext();

        $this->queue = $this->context->createQueue('enqueue_amqp_publish_bench');
        $this->context->declareQueue($this->queue);
        $this->context->purgeQueue($this->queue);
    }

    public function beforeBenchConsume()
    {
        $this->context = $this->createContext();

        $this->queue = $this->context->createQueue('enqueue_amqp_consume_bench');
        $this->context->declareQueue($this->queue);
        $this->context->purgeQueue($this->queue);

        $producer = $this->context->createProducer();
        foreach (range(1, $this->messagesLimit) as $index) {
            $producer->send($this->queue, $this->context->createMessage($index));
        }
    }

    abstract protected function createContext();
}
