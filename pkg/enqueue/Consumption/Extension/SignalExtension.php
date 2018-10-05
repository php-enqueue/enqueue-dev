<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\Exception\LogicException;
use Enqueue\Consumption\ExtensionInterface;
use Psr\Log\LoggerInterface;

class SignalExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

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

    public function onPreReceived(Context $context)
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->setExecutionInterrupted(true);
        }
    }

    public function onPostReceived(Context $context)
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->setExecutionInterrupted(true);
        }
    }

    public function onIdle(Context $context)
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->setExecutionInterrupted(true);
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
