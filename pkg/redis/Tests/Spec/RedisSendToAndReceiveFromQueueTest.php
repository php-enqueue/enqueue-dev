<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
{
    use RedisExtension;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->buildPhpRedisContext();
    }
}
