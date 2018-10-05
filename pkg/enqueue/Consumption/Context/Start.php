<?php

namespace Enqueue\Consumption\Context;

use Enqueue\Consumption\BoundProcessor;
use Interop\Queue\Context;
use Psr\Log\LoggerInterface;

final class Start
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BoundProcessor[]
     */
    private $processors;

    /**
     * @var int
     */
    private $receiveTimeout;

    /**
     * @var int
     */
    private $idleTime;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var bool
     */
    private $executionInterrupted;

    /**
     * @param BoundProcessor[] $processors
     */
    public function __construct(Context $context, LoggerInterface $logger, array $processors, int $receiveTimeout, int $idleTime, int $startTime)
    {
        $this->context = $context;
        $this->logger = $logger;
        $this->processors = $processors;
        $this->receiveTimeout = $receiveTimeout;
        $this->idleTime = $idleTime;
        $this->startTime = $startTime;

        $this->executionInterrupted = false;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function changeLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * In milliseconds.
     */
    public function getReceiveTimeout(): int
    {
        return $this->receiveTimeout;
    }

    /**
     * In milliseconds.
     */
    public function changeReceiveTimeout(int $timeout): void
    {
        $this->receiveTimeout = $timeout;
    }

    /**
     * In milliseconds.
     */
    public function getIdleTime(): int
    {
        return $this->idleTime;
    }

    /**
     * In milliseconds.
     */
    public function changeIdleTime(int $time): void
    {
        $this->idleTime = $time;
    }

    /**
     * In milliseconds.
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @return BoundProcessor[]
     */
    public function getBoundProcessors(): array
    {
        return $this->processors;
    }

    /**
     * @param BoundProcessor[] $processors
     */
    public function changeBoundProcessors(array $processors): void
    {
        $this->processors = [];
        array_walk($processors, function (BoundProcessor $processor) {
            $this->processors[] = $processor;
        });
    }

    public function isExecutionInterrupted(): bool
    {
        return $this->executionInterrupted;
    }

    public function interruptExecution(): void
    {
        $this->executionInterrupted = true;
    }
}
