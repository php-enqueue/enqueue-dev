<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\ConsumptionExtension\SetupBrokerExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\ExtensionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait SetupBrokerExtensionCommandTrait
{
    /**
     * {@inheritdoc}
     */
    protected function configureSetupBrokerExtension()
    {
        $this
            ->addOption('setup-broker', null, InputOption::VALUE_NONE, 'Creates queues, topics, exchanges, binding etc on broker side.')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param DriverInterface $driver
     *
     * @return ExtensionInterface
     */
    protected function getSetupBrokerExtension(InputInterface $input, DriverInterface $driver)
    {
        if ($input->getOption('setup-broker')) {
            return new SetupBrokerExtension($driver);
        }
    }
}
