<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbMessage;
use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendAndReceivePriorityMessagesFromQueueSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbSendAndReceivePriorityMessagesFromQueueTest extends SendAndReceivePriorityMessagesFromQueueSpec
{
    use MongodbExtensionTrait;

    private $publishedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publishedAt = (int) (microtime(true) * 10000);
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        return $this->buildMongodbContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param MongodbContext $context
     *
     * @return MongodbMessage
     */
    protected function createMessage(Context $context, $body)
    {
        /** @var MongodbMessage $message */
        $message = parent::createMessage($context, $body);

        // in order to test priorities correctly we have to make sure the messages were sent in the same time.
        $message->setPublishedAt($this->publishedAt);

        return $message;
    }
}
