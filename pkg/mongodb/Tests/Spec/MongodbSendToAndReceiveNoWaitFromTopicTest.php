<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveNoWaitFromTopicSpec;

/**
 * @group functional
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
