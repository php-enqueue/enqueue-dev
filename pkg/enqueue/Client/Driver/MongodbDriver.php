<?php

namespace Enqueue\Client\Driver;

use Enqueue\Mongodb\MongodbContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method MongodbContext getContext
 */
class MongodbDriver extends GenericDriver
{
    public function __construct(MongodbContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[MongodbDriver] '.$text, ...$args));
        };

        $contextConfig = $this->getContext()->getConfig();
        $log('Creating database and collection: "%s" "%s"', $contextConfig['dbname'], $contextConfig['collection_name']);
        $this->getContext()->createCollection();
    }
}
