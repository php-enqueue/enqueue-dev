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
        $queue = parent::doCreateQueue($transportQueueName);
        $queue->setArguments(['x-max-priority' => 4]);

        return $queue;
    }
}
