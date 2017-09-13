<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveNoWaitFromTopicSpec;

/**
 * @group functional
 */
class DbalSendToAndReceiveNoWaitFromTopicTest extends SendToAndReceiveNoWaitFromTopicSpec
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
