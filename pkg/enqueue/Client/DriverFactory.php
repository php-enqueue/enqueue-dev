<?php

namespace Enqueue\Client;

use Enqueue\Psr\PsrConnectionFactory;

interface DriverFactory
{
    /**
     * @param PsrConnectionFactory $connectionFactory
     * @param array                $config
     *
     * @return DriverInterface
     */
    public function createDriver(PsrConnectionFactory $connectionFactory, array $config);
}
