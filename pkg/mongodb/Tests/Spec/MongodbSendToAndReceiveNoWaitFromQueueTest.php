<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Spec\SendToAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbSendToAndReceiveNoWaitFromQueueTest extends SendToAndReceiveNoWaitFromQueueSpec
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
