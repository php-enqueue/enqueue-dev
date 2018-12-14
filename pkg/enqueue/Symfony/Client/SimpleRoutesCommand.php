<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Container\Container;

class SimpleRoutesCommand extends RoutesCommand
{
    public function __construct(DriverInterface $driver)
    {
        parent::__construct(
            new Container(['driver' => $driver]),
            'default',
            'driver'
        );
    }
}
