<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\StartExtensionInterface;

class SetupBrokerExtension implements StartExtensionInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var bool
     */
    private $isDone;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->isDone = false;
    }

    public function onStart(Start $context): void
    {
        if (false == $this->isDone) {
            $this->isDone = true;
            $this->driver->setupBroker($context->getLogger());
        }
    }
}
