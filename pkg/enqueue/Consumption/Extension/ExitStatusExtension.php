<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\EndExtensionInterface;

class ExitStatusExtension implements EndExtensionInterface
{
    /**
     * @var int
     */
    private $exitStatus;

    public function onEnd(End $context): void
    {
        $this->exitStatus = $context->getExitStatus();
    }

    public function getExitStatus(): ?int
    {
        return $this->exitStatus;
    }
}
