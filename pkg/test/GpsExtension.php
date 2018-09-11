<?php

namespace Enqueue\Test;

use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\GpsContext;

trait GpsExtension
{
    private function buildGpsContext(): GpsContext
    {
        if (false == getenv('GPS_DSN')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = getenv('GPS_DSN');

        return (new GpsConnectionFactory($config))->createContext();
    }
}
