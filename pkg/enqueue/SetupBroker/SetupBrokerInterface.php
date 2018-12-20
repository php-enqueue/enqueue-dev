<?php
namespace Enqueue\SetupBroker;

use Psr\Log\LoggerInterface;

interface SetupBrokerInterface
{
    public function setupBroker(LoggerInterface $logger = null): void;
}
