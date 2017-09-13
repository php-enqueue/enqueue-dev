<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\PsrProducerSpec;

/**
 * @group functional
 */
class DbalProducerTest extends PsrProducerSpec
{
    use CreateDbalContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        return $this->createDbalContext()->createProducer();
    }
}
