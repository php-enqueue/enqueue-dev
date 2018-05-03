<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\SendAndReceiveTimeToLiveMessagesFromQueueSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbSendAndReceiveTimeToLiveMessagesFromQueueTest extends SendAndReceiveTimeToLiveMessagesFromQueueSpec
{
    use CreateMongodbContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createMongodbContext();
    }
}
