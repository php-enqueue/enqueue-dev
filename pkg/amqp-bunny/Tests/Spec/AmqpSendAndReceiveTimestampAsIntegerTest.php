<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\SendAndReceiveTimestampAsIntegerSpec;

/**
 * @group functional
 */
class AmqpSendAndReceiveTimestampAsIntegerTest extends SendAndReceiveTimestampAsIntegerSpec
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
