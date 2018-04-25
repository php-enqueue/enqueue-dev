<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class DbalMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new DbalMessage();
    }
}
