<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\BasicConsumeShouldAddConsumerTagOnSubscribeSpec;

/**
 * @group functional
 */
class AmqpBasicConsumeShouldAddConsumerTagOnSubscribeTest extends BasicConsumeShouldAddConsumerTagOnSubscribeSpec
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
