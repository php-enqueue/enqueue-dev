<?php

namespace Enqueue;

use Interop\Queue\PsrConnectionFactory;

interface ConnectionFactoryFactoryInterface
{
    public function create(string $dsn): PsrConnectionFactory;
}
