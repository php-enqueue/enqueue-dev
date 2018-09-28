<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Test\SqsExtension;
use Interop\Queue\Spec\ProducerSpec;

/**
 * @group functional
 */
class SqsProducerTest extends ProducerSpec
{
    use SqsExtension;

    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        return $this->buildSqsContext()->createProducer();
    }
}
