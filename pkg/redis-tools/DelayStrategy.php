<?php

namespace Enqueue\RedisTools;

use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;

interface DelayStrategy
{
    /**
     * @param RedisContext     $context
     * @param RedisDestination $dest
     * @param RedisMessage     $message
     * @param int              $delayMsec
     */
    public function delayMessage(RedisContext $context, RedisDestination $dest, RedisMessage $message, $delayMsec);

    /**
     * Search for delayed message and move them on main queue.
     *
     * @param RedisContext     $context
     * @param RedisDestination $dest
     */
    public function processDelayedMessage(RedisContext $context, RedisDestination $dest);
}
