<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Spec\PsrProducerSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbProducerTest extends PsrProducerSpec
{
    use MongodbExtensionTrait;

    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        return $this->buildMongodbContext()->createProducer();
    }
}
