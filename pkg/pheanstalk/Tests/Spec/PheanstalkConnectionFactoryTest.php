<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

class PheanstalkConnectionFactoryTest extends ConnectionFactorySpec
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new PheanstalkConnectionFactory();
    }
}
