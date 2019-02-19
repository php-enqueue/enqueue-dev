<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\SnsQs\SnsQsConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

class SnsQsConnectionFactoryTest extends ConnectionFactorySpec
{
    /**
     * @return ConnectionFactory
     */
    protected function createConnectionFactory()
    {
        return new SnsQsConnectionFactory('snsqs:');
    }
}
