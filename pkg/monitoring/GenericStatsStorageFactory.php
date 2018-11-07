<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

use Enqueue\Dsn\Dsn;

class GenericStatsStorageFactory implements StatsStorageFactory
{
    public function create(string $dsn): StatsStorage
    {
        $schema = (new Dsn($dsn))->getScheme();

        switch ($schema) {
            case 'influxdb':
                return new InfluxDbStorage($dsn);
            case 'wamp':
            case 'ws':
                return new WampStorage($dsn);
            default:
                throw new \LogicException(sprintf('Unsupported stats storage: "%s"', $dsn));
        }
    }
}
