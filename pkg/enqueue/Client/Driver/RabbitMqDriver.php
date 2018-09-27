<?php

namespace  Enqueue\Client\Driver;

use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrQueue;

class RabbitMqDriver extends AmqpDriver
{
    /**
     * @return AmqpQueue
     */
    protected function doCreateQueue(string $transportQueueName): PsrQueue
    {
        $queue = parent::doCreateQueue($transportQueueName);
        $queue->setArguments(['x-max-priority' => 4]);

        return $queue;
    }
}
