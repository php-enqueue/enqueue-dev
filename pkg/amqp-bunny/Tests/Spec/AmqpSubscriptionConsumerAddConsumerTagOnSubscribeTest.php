<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Queue\Spec\Amqp\SubscriptionConsumerAddConsumerTagOnSubscribeSpec;

/**
 * @group functional
 */
class AmqpSubscriptionConsumerAddConsumerTagOnSubscribeTest extends SubscriptionConsumerAddConsumerTagOnSubscribeSpec
{
    protected function createContext(): AmqpContext
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext();
    }
}
