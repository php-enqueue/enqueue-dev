<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

class GpsConnectionFactoryTest extends PsrConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new GpsConnectionFactory();
    }
}
