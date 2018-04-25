<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
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
