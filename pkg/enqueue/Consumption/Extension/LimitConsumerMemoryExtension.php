<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumerMemoryExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var int
     */
    protected $memoryLimit;

    /**
     * @param int $memoryLimit Megabytes
     */
    public function __construct($memoryLimit)
    {
        if (false == is_int($memoryLimit)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected memory limit is int but got: "%s"',
                is_object($memoryLimit) ? get_class($memoryLimit) : gettype($memoryLimit)
            ));
        }

        $this->memoryLimit = $memoryLimit * 1024 * 1024;
    }

    public function onPreConsume(PreConsume $context): void
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
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

    protected function shouldBeStopped(LoggerInterface $logger): bool
    {
        $memoryUsage = memory_get_usage(true);
        if ($memoryUsage >= $this->memoryLimit) {
            $logger->debug(sprintf(
                '[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached. limit: "%s", used: "%s"',
                $this->memoryLimit,
                $memoryUsage
            ));

            return true;
        }

        return false;
    }
}
