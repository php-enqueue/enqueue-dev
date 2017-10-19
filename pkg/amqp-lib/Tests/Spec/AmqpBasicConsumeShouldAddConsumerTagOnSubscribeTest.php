<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
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
