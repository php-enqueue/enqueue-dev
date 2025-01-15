<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Test\RedisExtension;
use Interop\Queue\Context;
use Interop\Queue\Spec\SubscriptionConsumerStopOnFalseSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisSubscriptionConsumerStopOnFalseTest extends SubscriptionConsumerStopOnFalseSpec
{
    use RedisExtension;

    /**
     * @return RedisContext
     */
    protected function createContext()
    {
        return $this->buildPhpRedisContext();
    }

    /**
     * @param RedisContext $context
     */
    protected function createQueue(Context $context, $queueName)
    {
        /** @var RedisDestination $queue */
        $queue = parent::createQueue($context, $queueName);
        $context->getRedis()->del($queueName);

        return $queue;
    }
}
