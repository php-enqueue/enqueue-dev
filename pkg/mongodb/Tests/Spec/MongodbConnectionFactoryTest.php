<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

/**
 * @group mongodb
 */
class MongodbConnectionFactoryTest extends ConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new MongodbConnectionFactory();
    }
}
