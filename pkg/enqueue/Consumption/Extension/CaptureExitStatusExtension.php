<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\EndExtensionInterface;

class CaptureExitStatusExtension implements EndExtensionInterface
{
    /**
     * @var int
     */
    private $exitStatus;

    /**
     * @var bool
     */
    private $isExitStatusCaptured = false;

    public function onEnd(End $context): void
    {
        $this->exitStatus = $context->getExitStatus();
        $this->isExitStatusCaptured = true;
    }

    public function getExitStatus(): ?int
    {
        return $this->exitStatus;
    }

    public function isExitStatusCaptured(): bool
    {
        return $this->isExitStatusCaptured;
    }
}
