<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
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
