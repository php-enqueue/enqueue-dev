<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Test\SqsExtension;
use Interop\Queue\Spec\PsrProducerSpec;

/**
 * @group functional
 */
class SqsProducerTest extends PsrProducerSpec
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
