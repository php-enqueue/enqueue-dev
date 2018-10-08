<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\PostConsumeExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\PreConsumeExtensionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumptionTimeExtension implements PreConsumeExtensionInterface, PostConsumeExtensionInterface, PostMessageReceivedExtensionInterface
{
    /**
     * @var \DateTime
     */
    protected $timeLimit;

    /**
     * @param \DateTime $timeLimit
     */
    public function __construct(\DateTime $timeLimit)
    {
        $this->timeLimit = $timeLimit;
    }

    public function onPreConsume(PreConsume $context): void
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

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    protected function shouldBeStopped(LoggerInterface $logger): bool
    {
        $now = new \DateTime();
        if ($now >= $this->timeLimit) {
            $logger->debug(sprintf(
                '[LimitConsumptionTimeExtension] Execution interrupted as limit time has passed.'.
                ' now: "%s", time-limit: "%s"',
                $now->format(DATE_ISO8601),
                $this->timeLimit->format(DATE_ISO8601)
            ));

            return true;
        }

        return false;
    }
}
