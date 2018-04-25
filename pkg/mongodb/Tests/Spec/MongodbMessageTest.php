<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class MongodbMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new MongodbMessage();
    }
}
