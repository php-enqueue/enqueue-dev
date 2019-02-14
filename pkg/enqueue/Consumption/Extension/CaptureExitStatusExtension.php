<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\ExtensionInterface;

class CaptureExitStatusExtension implements ExtensionInterface
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

    public function onInitLogger(\Enqueue\Consumption\Context\InitLogger $context): void
    {
    }

    public function onMessageReceived(\Enqueue\Consumption\Context\MessageReceived $context): void
    {
    }

    public function onPostConsume(\Enqueue\Consumption\Context\PostConsume $context): void
    {
    }

    public function onPostMessageReceived(\Enqueue\Consumption\Context\PostMessageReceived $context): void
    {
    }

    public function onPreConsume(\Enqueue\Consumption\Context\PreConsume $context): void
    {
    }

    public function onPreSubscribe(\Enqueue\Consumption\Context\PreSubscribe $context): void
    {
    }

    public function onProcessorException(\Enqueue\Consumption\Context\ProcessorException $context): void
    {
    }

    public function onResult(\Enqueue\Consumption\Context\MessageResult $context): void
    {
    }

    public function onStart(\Enqueue\Consumption\Context\Start $context): void
    {
    }
}
