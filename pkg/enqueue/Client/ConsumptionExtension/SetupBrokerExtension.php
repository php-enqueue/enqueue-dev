<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\ChainExtension;
use Enqueue\Client\ExtensionInterface as ClientExtensionInterface;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class SetupBrokerExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var ClientExtensionInterface
     */
    private $extension;

    /**
     * @var bool
     */
    private $isDone;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver, ClientExtensionInterface $extension = null)
    {
        $this->driver = $driver;
        $this->extension = $extension ?: new ChainExtension([]);
        $this->isDone = false;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        if (false == $this->isDone) {
            $this->isDone = true;
            $this->driver->setupBroker($context->getLogger());
            $this->extension->onPostSetupBroker($this->driver->getContext(), $context->getLogger());
        }
    }
}
