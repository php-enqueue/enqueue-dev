<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

class GpsConnectionFactoryTest extends ConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new GpsConnectionFactory();
    }
}
