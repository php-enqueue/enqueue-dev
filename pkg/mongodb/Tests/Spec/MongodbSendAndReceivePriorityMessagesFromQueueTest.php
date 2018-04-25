<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendAndReceivePriorityMessagesFromQueueSpec;

/**
 * @group functional
 */
class MongodbSendAndReceivePriorityMessagesFromQueueTest extends SendAndReceivePriorityMessagesFromQueueSpec
{
    use CreateMongodbContextTrait;

    private $publishedAt;

    public function setUp()
    {
        parent::setUp();

        $this->publishedAt = (int) (microtime(true) * 10000);
    }

    /**
     * @return PsrContext
     */
    protected function createContext()
    {
        return $this->createMongodbContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param MongodbContext $context
     *
     * @return MongodbMessage
     */
    protected function createMessage(PsrContext $context, $body)
    {
        /** @var MongodbMessage $message */
        $message = parent::createMessage($context, $body);

        // in order to test priorities correctly we have to make sure the messages were sent in the same time.
        $message->setPublishedAt($this->publishedAt);

        return $message;
    }
}
