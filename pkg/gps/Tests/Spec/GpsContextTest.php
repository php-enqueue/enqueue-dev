<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsContext;
use Google\Cloud\PubSub\PubSubClient;
use Interop\Queue\Spec\PsrContextSpec;

class GpsContextTest extends PsrContextSpec
{
    protected function createContext()
    {
        return new GpsContext($this->createMock(PubSubClient::class));
    }
}
