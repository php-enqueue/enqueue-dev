<?php

namespace Enqueue\Client;

use Interop\Queue\ConnectionFactory;

interface DriverFactoryInterface
{
    public function create(ConnectionFactory $factory, Config $config, RouteCollection $collection): DriverInterface;
}
