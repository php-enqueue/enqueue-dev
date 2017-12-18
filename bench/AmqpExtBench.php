<?php

namespace Enqueue\Bench;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * @OutputTimeUnit("seconds", precision=3)
 * @Iterations(5)
 */
class AmqpExtBench
{
    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * @var string
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
        /** @var \AMQPExchange $destination */
        $amqpExchange = new \AMQPExchange($this->channel);
        $amqpExchange->setType(AMQP_EX_TYPE_DIRECT);
        $amqpExchange->setName('');

        for ($i = 0; $i < $this->messagesLimit; ++$i) {
            $amqpExchange->publish($this->body, $this->queue);
        }
    }

//    /**
//     * @BeforeMethods({"beforeBenchConsume"})
//     */
//    public function benchConsume()
//    {
    //$count = 0;
    //$callback = function($msg) use (&$count, $channel) {
//            $count++;
//
//            if ($count >= 100000) {
//                $channel->callbacks = [];
//            }
    //};
//
    //$startConsumeTime = microtime(true);
    //$startConsumeMemory = memory_get_usage();
//
    //echo 'Consuming...'.PHP_EOL;
//
    //$channel->basic_consume('amqp_lib_bench', 'amqp_lib', false, true, false, false, $callback);
    //while(count($channel->callbacks)) {
//    $channel->wait();
    //}
//
    //$endConsumeTime = microtime(true);
    //$endConsumeMemory = memory_get_usage();
//
    //$channel->close();
    //$connection->close();
//
    //echo sprintf('Publish took %s seconds, %skb memory', $endPublishTime - $startPublishTime, ($endPublishMemory - $startPublishMemory) / 1000).PHP_EOL;
    //echo sprintf('Consume took %s seconds, %skb memory', $endConsumeTime - $startConsumeTime, ($endConsumeMemory - $startConsumeMemory) / 1000).PHP_EOL;

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

        $this->queue = 'amqp_bunny_bench';

        $extConfig = [];
        $extConfig['host'] = getenv('RABBITMQ_HOST');
        $extConfig['port'] = getenv('RABBITMQ_AMQP_PORT');
        $extConfig['vhost'] = getenv('RABBITMQ_VHOST');
        $extConfig['login'] = getenv('RABBITMQ_USER');
        $extConfig['password'] = getenv('RABBITMQ_PASSWORD');

        $connection = new \AMQPConnection($extConfig);
        $connection->pconnect();

        $this->channel = new \AMQPChannel($connection);

        $queue = new \AMQPQueue($this->channel);
        $queue->setName($this->queue);
        $queue->declareQueue();
        $queue->purge();
    }

    public function beforeBenchConsume()
    {
//        $this->channel = $this->createContext();
//
//        $this->queue = $this->channel->createQueue('enqueue_amqp_consume_bench');
//        $this->channel->declareQueue($this->queue);
//        $this->channel->purgeQueue($this->queue);
//
//        $producer = $this->channel->createProducer();
//        foreach (range(1, $this->messagesLimit) as $index) {
//            $producer->send($this->queue, $this->channel->createMessage($index));
//        }
    }
}
