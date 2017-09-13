<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

class DbalConnectionFactoryTest extends PsrConnectionFactorySpec
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new DbalConnectionFactory();
    }
}
