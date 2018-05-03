<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
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
