<?php
namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 */
class RdKafkaSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    protected function createContext()
    {
        return (new RdKafkaConnectionFactory(getenv('RDKAFKA_DSN')))->createContext();
    }
}
