<?php

namespace Enqueue\Consumption\Context;

use Interop\Queue\Context;
use Psr\Log\LoggerInterface;

final class End
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var int
     */
    private $endTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $exitStatus;

    public function __construct(
        Context $context,
        int $startTime,
        int $endTime,
        LoggerInterface $logger,
        ?int $exitStatus = null
    ) {
        $this->context = $context;
        $this->logger = $logger;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->exitStatus = $exitStatus;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * In milliseconds.
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * In milliseconds.
     */
    public function getEndTime(): int
    {
        return $this->startTime;
    }

    public function getExitStatus(): ?int
    {
        return $this->exitStatus;
    }
}
