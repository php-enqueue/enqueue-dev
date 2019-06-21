<?php

namespace  Enqueue\Client\Driver;

use Interop\Amqp\AmqpQueue;
use Interop\Queue\Queue as InteropQueue;

class RabbitMqDriver extends AmqpDriver
{
    /**
     * @return AmqpQueue
     */
    protected function doCreateQueue(string $transportQueueName): InteropQueue
    {
        $config = parent::getConfig();
        $queue = parent::doCreateQueue($transportQueueName);

        $driverOptions = $config->getDriverOptions();
        $islazyQueue = false;
        $lazyQueueArray = array_filter($driverOptions, function ($arr) {
            return array_key_exists('rabbit_mq_lazy_queues', $arr);
        });

        $islazyQueue = array_key_exists($transportQueueName, $lazyQueueArray[0]['rabbit_mq_lazy_queues']);

        $queue->setArguments(['x-max-priority' => 4]);
        if ($islazyQueue) {
            $queue->setArguments(['x-queue-mode' => 'lazy']);
        }

        return $queue;
    }
}
