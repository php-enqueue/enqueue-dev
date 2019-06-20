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

        $queue->setArguments(['x-max-priority' => 4]);

        // we use isLazy to avoid collisions with 'lazy' which
        // is already used to meansomething else
        if (true == $config->getTransportOption('isLazy')) {
            $queue->setArguments(['x-queue-mode' => 'lazy']);
        }

        return $queue;
    }
}
