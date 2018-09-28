<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalMessage;
use Interop\Queue\Spec\MessageSpec;

class DbalMessageTest extends MessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new DbalMessage();
    }
}
