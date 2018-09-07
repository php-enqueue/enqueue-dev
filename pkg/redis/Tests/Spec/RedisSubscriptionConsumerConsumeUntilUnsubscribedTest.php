<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Test\RedisExtension;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SubscriptionConsumerConsumeUntilUnsubscribedSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisSubscriptionConsumerConsumeUntilUnsubscribedTest extends SubscriptionConsumerConsumeUntilUnsubscribedSpec
{
    use RedisExtension;

    /**
     * @return RedisContext
     *
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->buildPhpRedisContext();
    }

    /**
     * @param RedisContext $context
     *
     * {@inheritdoc}
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        /** @var RedisDestination $queue */
        $queue = parent::createQueue($context, $queueName);
        $context->getRedis()->del($queueName);

        return $queue;
    }
}
