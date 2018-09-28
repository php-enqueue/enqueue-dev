<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

class DbalConnectionFactoryTest extends ConnectionFactorySpec
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new DbalConnectionFactory();
    }
}
