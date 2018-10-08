<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Exception\LogicException;
use Enqueue\Consumption\PostConsumeExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\PreConsumeExtensionInterface;
use Enqueue\Consumption\StartExtensionInterface;
use Psr\Log\LoggerInterface;

class SignalExtension implements StartExtensionInterface, PreConsumeExtensionInterface, PostMessageReceivedExtensionInterface, PostConsumeExtensionInterface
{
    /**
     * @var bool
     */
    protected $interruptConsumption = false;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    public function onStart(Start $context): void
    {
        if (false == extension_loaded('pcntl')) {
            throw new LogicException('The pcntl extension is required in order to catch signals.');
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);

        $this->logger = $context->getLogger();
        $this->interruptConsumption = false;
    }

    public function onPreConsume(PreConsume $context): void
    {
        $this->logger = $context->getLogger();

        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    public function onPostConsume(PostConsume $context): void
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    public function handleSignal(int $signal): void
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('[SignalExtension] Caught signal: %s', $signal));
        }

        switch ($signal) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
                if ($this->logger) {
                    $this->logger->debug('[SignalExtension] Interrupt consumption');
                }

                $this->interruptConsumption = true;
                break;
            default:
                break;
        }
    }

    private function shouldBeStopped(LoggerInterface $logger): bool
    {
        if ($this->interruptConsumption) {
            $logger->debug('[SignalExtension] Interrupt execution');

            $this->interruptConsumption = false;

            return true;
        }

        return false;
    }
}
