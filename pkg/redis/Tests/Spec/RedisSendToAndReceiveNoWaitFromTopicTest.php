<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\SendToAndReceiveNoWaitFromTopicSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisSendToAndReceiveNoWaitFromTopicTest extends SendToAndReceiveNoWaitFromTopicSpec
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
