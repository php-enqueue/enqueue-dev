<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Queue\Spec\Amqp\SubscriptionConsumerRemoveConsumerTagOnUnsubscribeSpec;

/**
 * @group functional
 */
class AmqpSubscriptionConsumerRemoveConsumerTagOnUnsubscribeTest extends SubscriptionConsumerRemoveConsumerTagOnUnsubscribeSpec
{
    public function test()
    {
        $this->markTestIncomplete('Seg fault.');
    }

    protected function createContext(): AmqpContext
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext();
    }
}
