<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\RequeueMessageSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisRequeueMessageTest extends RequeueMessageSpec
{
    use RedisExtension;

    protected function createContext()
    {
        return $this->buildPhpRedisContext();
    }
}
