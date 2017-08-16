<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsProducer;
use Interop\Queue\Spec\PsrProducerSpec;

class GpsProducerTest extends PsrProducerSpec
{
    protected function createProducer()
    {
        return new GpsProducer($this->createMock(GpsContext::class));
    }
}
