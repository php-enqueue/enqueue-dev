<?php

declare(strict_types=1);

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Spec\SubscriptionConsumerStopOnFalseSpec;

/**
 * @group functional
 * @group Mongodb
 */
class MongodbSubscriptionConsumerStopOnFalseTest extends SubscriptionConsumerStopOnFalseSpec
{
    use MongodbExtensionTrait;

    /**
     * @return MongodbContext
     */
    protected function createContext()
    {
        return $this->buildMongodbContext();
    }

    /**
     * @param MongodbContext $context
     */
    protected function createQueue(Context $context, $queueName)
    {
        /** @var MongodbDestination $queue */
        $queue = parent::createQueue($context, $queueName);
        $context->getClient()->dropDatabase($queueName);

        return $queue;
    }
}
