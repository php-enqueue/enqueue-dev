<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Container\Container;

class SimpleConsumeCommand extends ConsumeCommand
{
    public function __construct(QueueConsumerInterface $consumer)
    {
        parent::__construct(
            new Container(['queue_consumer' => $consumer]),
            'default',
            'queue_consumer'
        );
    }
}
