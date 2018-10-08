<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\PreConsumeExtensionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumedMessagesExtension implements PreConsumeExtensionInterface, PostMessageReceivedExtensionInterface
{
    /**
     * @var int
     */
    protected $messageLimit;

    /**
     * @var int
     */
    protected $messageConsumed;

    /**
     * @param int $messageLimit
     */
    public function __construct(int $messageLimit)
    {
        $this->messageLimit = $messageLimit;
        $this->messageConsumed = 0;
    }

    public function onPreConsume(PreConsume $context): void
    {
        // this is added here to handle an edge case. when a user sets zero as limit.
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        ++$this->messageConsumed;

        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    protected function shouldBeStopped(LoggerInterface $logger): bool
    {
        if ($this->messageConsumed >= $this->messageLimit) {
            $logger->debug(sprintf(
                '[LimitConsumedMessagesExtension] Message consumption is interrupted since the message limit reached.'.
                ' limit: "%s"',
                $this->messageLimit
            ));

            return true;
        }

        return false;
    }
}
