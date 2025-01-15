<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Spec\RequeueMessageSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbRequeueMessageTest extends RequeueMessageSpec
{
    use MongodbExtensionTrait;

    protected function createContext()
    {
        return $this->buildMongodbContext();
    }
}
