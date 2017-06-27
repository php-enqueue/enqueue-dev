<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanMessage;
use Enqueue\Psr\Spec\PsrMessageSpec;

class GearmanMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new GearmanMessage();
    }
}
