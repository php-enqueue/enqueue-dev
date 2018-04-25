<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\BasicConsumeUntilUnsubscribedSpec;

/**
 * @group functional
 */
class AmqpBasicConsumeUntilUnsubscribedTest extends BasicConsumeUntilUnsubscribedSpec
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
