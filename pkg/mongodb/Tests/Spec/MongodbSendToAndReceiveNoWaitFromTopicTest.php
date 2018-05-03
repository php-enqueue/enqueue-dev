<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveNoWaitFromTopicSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbSendToAndReceiveNoWaitFromTopicTest extends SendToAndReceiveNoWaitFromTopicSpec
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
