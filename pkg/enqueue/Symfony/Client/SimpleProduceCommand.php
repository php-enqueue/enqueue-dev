<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\ProducerInterface;
use Enqueue\Container\Container;

class SimpleProduceCommand extends ProduceCommand
{
    public function __construct(ProducerInterface $producer)
    {
        parent::__construct(
            new Container(['producer' => $producer]),
            'default',
            'producer'
        );
    }
}
