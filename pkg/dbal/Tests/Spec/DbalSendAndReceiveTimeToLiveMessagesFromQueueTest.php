<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\SendAndReceiveTimeToLiveMessagesFromQueueSpec;

/**
 * @group functional
 */
class DbalSendAndReceiveTimeToLiveMessagesFromQueueTest extends SendAndReceiveTimeToLiveMessagesFromQueueSpec
{
    use CreateDbalContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createDbalContext();
    }
}
