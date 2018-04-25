<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

class MongodbConnectionFactoryTest extends PsrConnectionFactorySpec
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new MongodbConnectionFactory();
    }
}
