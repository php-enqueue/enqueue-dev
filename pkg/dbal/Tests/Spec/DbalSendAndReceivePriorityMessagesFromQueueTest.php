<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendAndReceivePriorityMessagesFromQueueSpec;

/**
 * @group functional
 */
class DbalSendAndReceivePriorityMessagesFromQueueTest extends SendAndReceivePriorityMessagesFromQueueSpec
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
        return $this->createDbalContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalContext $context
     *
     * @return DbalMessage
     */
    protected function createMessage(PsrContext $context, $body)
    {
        /** @var DbalMessage $message */
        $message = parent::createMessage($context, $body);

        // in order to test priorities correctly we have to make sure the messages were sent in the same time.
        $message->setPublishedAt($this->publishedAt);

        return $message;
    }
}
