<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\BasicConsumeFromAllSubscribedQueuesSpec;

/**
 * @group functional
 */
class AmqpBasicConsumeFromAllSubscribedQueuesTest extends BasicConsumeFromAllSubscribedQueuesSpec
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
