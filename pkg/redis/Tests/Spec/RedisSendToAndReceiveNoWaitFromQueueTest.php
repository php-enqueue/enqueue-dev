<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\SendToAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisSendToAndReceiveNoWaitFromQueueTest extends SendToAndReceiveNoWaitFromQueueSpec
{
    use RedisExtension;

    protected function createContext()
    {
        return $this->buildPhpRedisContext();
    }
}
