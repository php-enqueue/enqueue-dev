<?php

namespace Enqueue\Dbal\Tests\Spec\Mysql;

use Interop\Queue\Spec\ProducerSpec;

/**
 * @group functional
 */
class DbalProducerTest extends ProducerSpec
{
    use CreateDbalContextTrait;

    protected function createProducer()
    {
        return $this->createDbalContext()->createProducer();
    }
}
