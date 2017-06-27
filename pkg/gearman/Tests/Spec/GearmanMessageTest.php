<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanMessage;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Enqueue\Psr\Spec\PsrMessageSpec;

class GearmanMessageTest extends PsrMessageSpec
{
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new GearmanMessage();
    }
}
