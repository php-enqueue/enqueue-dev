<?php

namespace  Enqueue\Client\Driver;

use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrQueue;

final class RabbitMqDriver extends AmqpDriver
{
    /**
     * @return AmqpQueue
     */
    public function createQueue(string $queueName): PsrQueue
    {
        $queue = parent::createQueue($queueName);
        $queue->setArguments(['x-max-priority' => 4]);

        return $queue;
    }
}
