<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\RequeueMessageSpec;

/**
 * @group functional
 */
class DbalRequeueMessageTest extends RequeueMessageSpec
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
