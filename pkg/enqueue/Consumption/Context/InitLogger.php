<?php

namespace Enqueue\Consumption\Context;

use Psr\Log\LoggerInterface;

class InitLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function changeLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
