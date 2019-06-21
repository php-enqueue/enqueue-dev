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
        $lazyQueueArray = $driverOptions['rabbit_mq_lazy_queues'] ?? [];
        $isLazyQueue = in_array($transportQueueName, $lazyQueueArray, true);

        $queue->setArguments(['x-max-priority' => 4]);

        if ($isLazyQueue) {
            $queue->setArgument('x-queue-mode', 'lazy');
        }

        return $queue;
    }
}
