<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisMessage;
use Interop\Queue\Spec\MessageSpec;

/**
 * @group Redis
 */
class RedisMessageTest extends MessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new RedisMessage();
    }
}
