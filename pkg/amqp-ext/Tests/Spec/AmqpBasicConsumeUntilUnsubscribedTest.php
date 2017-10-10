<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\BasicConsumeUntilUnsubscribedSpec;

/**
 * @group functional
 */
class AmqpBasicConsumeUntilUnsubscribedTest extends BasicConsumeUntilUnsubscribedSpec
{
    public function test()
    {
        $this->markTestSkipped('Sig fault');
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
