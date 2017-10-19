<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\BasicConsumeShouldRemoveConsumerTagOnUnsubscribeSpec;

/**
 * @group functional
 */
class AmqpBasicConsumeShouldRemoveConsumerTagOnUnsubscribeTest extends BasicConsumeShouldRemoveConsumerTagOnUnsubscribeSpec
{
    public function test()
    {
        $this->markTestIncomplete('Seg fault.');
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext();
    }
}
