<?php

namespace Enqueue\Dbal\Tests\Spec\Postgresql;

use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 */
class DbalSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
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
