<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

class AmqpConnectionFactoryTest extends PsrConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new AmqpConnectionFactory();
    }
}
