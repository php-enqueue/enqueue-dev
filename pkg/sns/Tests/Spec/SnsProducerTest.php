<?php

namespace Enqueue\Sns\Tests\Spec;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsProducer;
use Interop\Queue\Spec\ProducerSpec;

class SnsProducerTest extends ProducerSpec
{
    protected function createProducer()
    {
        return new SnsProducer($this->createMock(SnsContext::class));
    }
}
