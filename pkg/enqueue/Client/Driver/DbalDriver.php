<?php

namespace Enqueue\Client\Driver;

use Enqueue\Dbal\DbalContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method DbalContext getContext
 */
class DbalDriver extends GenericDriver
{
    public function __construct(DbalContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[DbalDriver] '.$text, ...$args));
        };

        $log('Creating database table: "%s"', $this->getContext()->getTableName());
        $this->getContext()->createDataBaseTable();
    }
}
