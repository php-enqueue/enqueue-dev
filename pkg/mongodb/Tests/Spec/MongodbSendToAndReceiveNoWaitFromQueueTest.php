<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 */
class MongodbSendToAndReceiveNoWaitFromQueueTest extends SendToAndReceiveNoWaitFromQueueSpec
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
