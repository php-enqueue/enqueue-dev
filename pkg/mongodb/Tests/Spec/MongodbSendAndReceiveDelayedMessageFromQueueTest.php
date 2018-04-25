<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\SendAndReceiveDelayedMessageFromQueueSpec;

/**
 * @group functional
 */
class MongodbSendAndReceiveDelayedMessageFromQueueTest extends SendAndReceiveDelayedMessageFromQueueSpec
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
