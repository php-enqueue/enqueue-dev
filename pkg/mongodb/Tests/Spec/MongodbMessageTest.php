<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbMessage;
use Interop\Queue\Spec\MessageSpec;

/**
 * @group mongodb
 */
class MongodbMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new MongodbMessage();
    }
}
