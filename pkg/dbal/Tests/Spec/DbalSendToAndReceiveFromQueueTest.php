<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class DbalSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
{
    use CreateMongodbContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createDbalContext();
    }
}
