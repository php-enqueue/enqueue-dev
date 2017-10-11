<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\PreFetchCountSpec;

/**
 * @group functional
 */
class AmqpPreFetchCountTest extends PreFetchCountSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext();
    }
}
