<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

class AmqpConnectionFactoryTest extends ConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new AmqpConnectionFactory();
    }
}
