<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\PostConsumeExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\PreConsumeExtensionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumerMemoryExtension implements PreConsumeExtensionInterface, PostMessageReceivedExtensionInterface, PostConsumeExtensionInterface
{
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
