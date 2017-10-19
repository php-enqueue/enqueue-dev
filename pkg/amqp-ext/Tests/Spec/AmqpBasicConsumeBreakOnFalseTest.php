<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\BasicConsumeBreakOnFalseSpec;

/**
 * @group functional
 */
class AmqpBasicConsumeBreakOnFalseTest extends BasicConsumeBreakOnFalseSpec
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
