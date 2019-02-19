<?php

declare(strict_types=1);

namespace Enqueue\Dbal\Tests\Spec\Postgresql;

use Enqueue\Dbal\DbalContext;
use Interop\Queue\Context;
use Interop\Queue\Spec\SubscriptionConsumerStopOnFalseSpec;

/**
 * @group functional
 * @group Dbal
 */
class DbalSubscriptionConsumerStopOnFalseTest extends SubscriptionConsumerStopOnFalseSpec
{
    use CreateDbalContextTrait;

    /**
     * @return DbalContext
     *
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createDbalContext();
    }

    /**
     * @param DbalContext $context
     *
     * {@inheritdoc}
     */
    protected function createQueue(Context $context, $queueName)
    {
        $queue = parent::createQueue($context, $queueName);
        $context->purgeQueue($queue);

        return $queue;
    }
}
