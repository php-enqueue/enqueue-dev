<?php

namespace Enqueue\Client;

use Interop\Queue\PsrConnectionFactory;

interface DriverFactoryInterface
{
    public function create(PsrConnectionFactory $factory, string $dsn, array $config): DriverInterface;
}
