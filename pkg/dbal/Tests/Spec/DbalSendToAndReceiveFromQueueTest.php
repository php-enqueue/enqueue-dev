<?php

namespace Interop\Queue\Spec;

use Enqueue\Dbal\Tests\Spec\CreateDbalContextTrait;

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
