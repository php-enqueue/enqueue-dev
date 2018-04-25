<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\SendAndReceiveDelayedMessageFromQueueSpec;

/**
 * @group functional
 */
class DbalSendAndReceiveDelayedMessageFromQueueTest extends SendAndReceiveDelayedMessageFromQueueSpec
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
