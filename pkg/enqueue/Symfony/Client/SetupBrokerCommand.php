<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\ChainExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\ExtensionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBrokerCommand extends Command
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var ExtensionInterface
     */
    private $extension;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver, ExtensionInterface $extension = null)
    {
        parent::__construct(null);

        $this->driver = $driver;
        $this->extension = $extension ?: new ChainExtension([]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('enqueue:setup-broker')
            ->setAliases(['enq:sb'])
            ->setDescription('Creates all required queues')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Setup Broker');

        $this->driver->setupBroker($logger = new ConsoleLogger($output));
        $this->extension->onPostSetupBroker($this->driver->getContext(), $logger);
    }
}
