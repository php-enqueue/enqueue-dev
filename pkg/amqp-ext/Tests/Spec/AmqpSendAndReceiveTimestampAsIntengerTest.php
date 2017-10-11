<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\Amqp\SendAndReceiveTimestampAsIntegerSpec;

/**
 * @group functional
 */
class AmqpSendAndReceiveTimestampAsIntengerTest extends SendAndReceiveTimestampAsIntegerSpec
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
