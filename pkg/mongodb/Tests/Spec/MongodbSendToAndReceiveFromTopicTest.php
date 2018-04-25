<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 */
class MongodbSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
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
