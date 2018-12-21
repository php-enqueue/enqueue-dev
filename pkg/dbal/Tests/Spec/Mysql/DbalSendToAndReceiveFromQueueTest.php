<?php

namespace Enqueue\Dbal\Tests\Spec\Mysql;

use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class DbalSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
{
    use CreateDbalContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createDbalContext();
    }
}
