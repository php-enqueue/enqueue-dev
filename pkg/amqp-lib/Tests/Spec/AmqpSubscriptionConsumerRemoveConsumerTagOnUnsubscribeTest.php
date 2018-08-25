<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Queue\Spec\Amqp\SubscriptionConsumerRemoveConsumerTagOnUnsubscribeSpec;

/**
 * @group functional
 */
class AmqpSubscriptionConsumerRemoveConsumerTagOnUnsubscribeTest extends SubscriptionConsumerRemoveConsumerTagOnUnsubscribeSpec
{
    protected function createContext(): AmqpContext
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext();
    }
}
