<?php
namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Interop\Queue\Spec\SendToAndReceiveNoWaitFromTopicSpec;

/**
 * @group functional
 */
class RdKafkaSendToAndReceiveNoWaitFromTopicTest extends SendToAndReceiveNoWaitFromTopicSpec
{
    protected function createContext()
    {
        return (new RdKafkaConnectionFactory(getenv('RDKAFKA_DSN')))->createContext();
    }
}
