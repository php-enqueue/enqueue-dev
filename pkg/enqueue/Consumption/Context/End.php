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

    public function __construct(Context $context, int $startTime, int $endTime, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->logger = $logger;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
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
}
