<?php

namespace Enqueue\Test;

use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\GpsContext;
use PHPUnit\Framework\SkippedTestError;

trait GpsExtension
{
    private function buildGpsContext(): GpsContext
    {
        if (false == getenv('GPS_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = getenv('GPS_DSN');

        return (new GpsConnectionFactory($config))->createContext();
    }
}
