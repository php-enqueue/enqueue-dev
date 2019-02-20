<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\Sns\SnsContext;
use Enqueue\SnsQs\SnsQsProducer;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\Spec\ProducerSpec;

class SnsQsProducerTest extends ProducerSpec
{
    protected function createProducer()
    {
        return new SnsQsProducer(
            $this->createMock(SnsContext::class),
            $this->createMock(SqsContext::class)
        );
    }
}
