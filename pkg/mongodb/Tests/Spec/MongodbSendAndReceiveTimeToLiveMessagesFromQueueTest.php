<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Spec\SendAndReceiveTimeToLiveMessagesFromQueueSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbSendAndReceiveTimeToLiveMessagesFromQueueTest extends SendAndReceiveTimeToLiveMessagesFromQueueSpec
{
    use MongodbExtensionTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->buildMongodbContext();
    }
}
