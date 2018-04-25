<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

class AmqpConnectionFactoryTest extends PsrConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new AmqpConnectionFactory();
    }
}
