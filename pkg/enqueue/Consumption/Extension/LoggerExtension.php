<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\InitLoggerExtensionInterface;
use Psr\Log\LoggerInterface;

class LoggerExtension implements InitLoggerExtensionInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onInitLogger(InitLogger $context): void
    {
        $previousLogger = $context->getLogger();

        if ($previousLogger !== $this->logger) {
            $context->changeLogger($this->logger);

            $this->logger->debug(sprintf('Change logger from "%s" to "%s"', get_class($previousLogger), get_class($this->logger)));
        }
    }
}
