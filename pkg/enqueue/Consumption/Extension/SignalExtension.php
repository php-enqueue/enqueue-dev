<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
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

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        if (false == extension_loaded('pcntl')) {
            throw new LogicException('The pcntl extension is required in order to catch signals.');
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);

        $this->interruptConsumption = false;
    }

    /**
     * @param Context $context
     */
    public function onBeforeReceive(Context $context)
    {
        $this->logger = $context->getLogger();

        $this->dispatchSignal();

        $this->interruptExecutionIfNeeded($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $this->interruptExecutionIfNeeded($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $this->dispatchSignal();

        $this->interruptExecutionIfNeeded($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $this->dispatchSignal();

        $this->interruptExecutionIfNeeded($context);
    }

    /**
     * @param Context $context
     */
    public function interruptExecutionIfNeeded(Context $context)
    {
        if (false == $context->isExecutionInterrupted() && $this->interruptConsumption) {
            if ($this->logger) {
                $this->logger->debug('[SignalExtension] Interrupt execution');
            }

            $context->setExecutionInterrupted($this->interruptConsumption);

            $this->interruptConsumption = false;
        }
    }

    /**
     * @param int $signal
     */
    public function handleSignal($signal)
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

    private function dispatchSignal()
    {
        if (false == function_exists('pcntl_async_signals')) {
            pcntl_signal_dispatch();
        }
    }
}
