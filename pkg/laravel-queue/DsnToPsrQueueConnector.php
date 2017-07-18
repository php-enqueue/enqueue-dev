<?php

namespace Enqueue\LaravelQueue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use function Enqueue\dsn_to_context;

class DsnToPsrQueueConnector implements ConnectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $config)
    {
        return new PsrQueue(
            dsn_to_context($config['dsn']),
            isset($config['default']) ? $config['default'] : 'default',
            isset($config['time_to_run']) ? $config['time_to_run'] : 0
        );
    }
}
