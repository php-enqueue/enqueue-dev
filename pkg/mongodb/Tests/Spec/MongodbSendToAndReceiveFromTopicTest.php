<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    use MongodbExtensionTrait;

    protected function createContext()
    {
        return $this->buildMongodbContext();
    }
}
