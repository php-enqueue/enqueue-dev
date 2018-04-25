<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\RequeueMessageSpec;

/**
 * @group functional
 */
class MongodbRequeueMessageTest extends RequeueMessageSpec
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
