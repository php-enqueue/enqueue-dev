<?php

namespace Enqueue\LaravelQueue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use function Enqueue\dsn_to_context;

class Connector implements ConnectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $config)
    {
        return new Queue(
            dsn_to_context($config['dsn']),
            isset($config['queue']) ? $config['queue'] : 'default',
            isset($config['time_to_run']) ? $config['time_to_run'] : 0
        );
    }
}
