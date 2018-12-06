<?php

namespace Enqueue\Sns\Tests\Spec;

use Enqueue\Sns\SnsConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

class SnsConnectionFactoryTest extends ConnectionFactorySpec
{
    /**
     * @return ConnectionFactory
     */
    protected function createConnectionFactory()
    {
        return new SnsConnectionFactory('sns:');
    }
}
