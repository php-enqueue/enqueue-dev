<?php

namespace Enqueue\Consumption\Context;

use Interop\Queue\Context;
use Interop\Queue\SubscriptionConsumer;
use Psr\Log\LoggerInterface;

final class PostConsume
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var SubscriptionConsumer
     */
    private $subscriptionConsumer;

    /**
     * @var int
     */
    private $receivedMessagesCount;

    /**
     * @var int
     */
    private $cycle;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $executionInterrupted;

    /**
     * @var int
     */
    private $exitStatus;

    public function __construct(Context $context, SubscriptionConsumer $subscriptionConsumer, int $receivedMessagesCount, int $cycle, int $startTime, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->subscriptionConsumer = $subscriptionConsumer;
        $this->receivedMessagesCount = $receivedMessagesCount;
        $this->cycle = $cycle;
        $this->startTime = $startTime;
        $this->logger = $logger;

        $this->executionInterrupted = false;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSubscriptionConsumer(): SubscriptionConsumer
    {
        return $this->subscriptionConsumer;
    }

    public function getReceivedMessagesCount(): int
    {
        return $this->receivedMessagesCount;
    }

    public function getCycle(): int
    {
        return $this->cycle;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getExitStatus(): ?int
    {
        return $this->exitStatus;
    }

    public function isExecutionInterrupted(): bool
    {
        return $this->executionInterrupted;
    }

    public function interruptExecution(?int $exitStatus = null): void
    {
        $this->exitStatus = $exitStatus;
        $this->executionInterrupted = true;
    }
}
