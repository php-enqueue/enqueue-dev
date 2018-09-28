<?php

namespace Enqueue\Client;

use Interop\Queue\ConnectionFactory;

interface DriverFactoryInterface
{
    public function create(ConnectionFactory $factory, string $dsn, array $config): DriverInterface;
}
