<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

class PheanstalkConnectionFactoryTest extends PsrConnectionFactorySpec
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new PheanstalkConnectionFactory();
    }
}
