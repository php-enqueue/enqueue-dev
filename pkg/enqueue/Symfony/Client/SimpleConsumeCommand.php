<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Container\Container;
use Interop\Queue\Processor;

class SimpleConsumeCommand extends ConsumeCommand
{
    public function __construct(QueueConsumerInterface $queueConsumer, DriverInterface $driver, Processor $processor)
    {
        parent::__construct(
            new Container([
                'queue_consumer' => $queueConsumer,
                'driver' => $driver,
                'processor' => $processor,
            ]),
            'default',
            'queue_consumer',
            'driver',
            'processor'
        );
    }
}
