<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendAndReceivePriorityMessagesFromQueueSpec;

/**
 * @group functional
 */
class DbalSendAndReceivePriorityMessagesFromQueueTest extends SendAndReceivePriorityMessagesFromQueueSpec
{
    use CreateDbalContextTrait;

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
     * @return DbalMessage
     */
    protected function createMessage(PsrContext $context, $priority)
    {
        /** @var DbalMessage $message */
        $message = $context->createMessage('priority'.$priority);
        $message->setPriority($priority);

        return $message;
    }
}
