<?php

namespace Enqueue\Dbal\Tests\Spec\Mysql;

use Interop\Queue\Spec\SendAndReceiveTimeToLiveMessagesFromQueueSpec;

/**
 * @group functional
 */
class DbalSendAndReceiveTimeToLiveMessagesFromQueueTest extends SendAndReceiveTimeToLiveMessagesFromQueueSpec
{
    use CreateDbalContextTrait;

    protected function createContext()
    {
        return $this->createDbalContext();
    }
}
