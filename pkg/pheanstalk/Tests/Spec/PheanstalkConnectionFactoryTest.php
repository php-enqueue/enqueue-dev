<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\Psr\Spec\PsrConnectionFactorySpec;

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
