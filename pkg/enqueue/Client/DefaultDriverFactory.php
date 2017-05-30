<?php

namespace Enqueue\Client;

use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Psr\PsrConnectionFactory;

class DefaultDriverFactory implements DriverFactory
{
    /**
     * @param Config            $config
     * @param QueueMetaRegistry $queueMetaRegistry
     */
    public function __construct(Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(PsrConnectionFactory $connectionFactory, array $config)
    {
        // TODO: Implement createDriver() method.
    }
}
