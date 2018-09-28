<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsProducer;
use Interop\Queue\Spec\ProducerSpec;

class GpsProducerTest extends ProducerSpec
{
    protected function createProducer()
    {
        return new GpsProducer($this->createMock(GpsContext::class));
    }
}
