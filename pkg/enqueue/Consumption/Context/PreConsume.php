<?php

namespace Enqueue\Consumption\Context;

use Interop\Queue\Context;
use Interop\Queue\SubscriptionConsumer;
use Psr\Log\LoggerInterface;

final class PreConsume
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $cycle;

    /**
     * @var int
     */
    private $receiveTimeout;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var bool
     */
    private $executionInterrupted;

    /**
     * @var int
     */
    private $exitStatus;

    public function __construct(Context $context, SubscriptionConsumer $subscriptionConsumer, LoggerInterface $logger, int $cycle, int $receiveTimeout, int $startTime)
    {
        $this->context = $context;
        $this->subscriptionConsumer = $subscriptionConsumer;
        $this->logger = $logger;
        $this->cycle = $cycle;
        $this->receiveTimeout = $receiveTimeout;
        $this->startTime = $startTime;

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

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getCycle(): int
    {
        return $this->cycle;
    }

    public function getReceiveTimeout(): int
    {
        return $this->receiveTimeout;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
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
