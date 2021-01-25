<?php

namespace Enqueue\Dbal\Tests\Spec\Mysql;

use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalMessage;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendAndReceivePriorityMessagesFromQueueSpec;

/**
 * @group functional
 */
class DbalSendAndReceivePriorityMessagesFromQueueTest extends SendAndReceivePriorityMessagesFromQueueSpec
{
    use CreateDbalContextTrait;

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
        return $this->createDbalContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalContext $context
     *
     * @return DbalMessage
     */
    protected function createMessage(Context $context, $body)
    {
        /** @var DbalMessage $message */
        $message = parent::createMessage($context, $body);

        // in order to test priorities correctly we have to make sure the messages were sent in the same time.
        $message->setPublishedAt($this->publishedAt);

        return $message;
    }
}
