<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanMessage;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Interop\Queue\Spec\MessageSpec;

class GearmanMessageTest extends MessageSpec
{
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    protected function createMessage()
    {
        return new GearmanMessage();
    }
}
