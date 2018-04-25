<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\PsrProducerSpec;

/**
 * @group functional
 */
class MongodbProducerTest extends PsrProducerSpec
{
    use CreateMongodbContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        return $this->createMongodbContext()->createProducer();
    }
}
