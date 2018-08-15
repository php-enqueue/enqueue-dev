<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBrokerCommand extends Command
{
    protected static $defaultName = 'enqueue:setup-broker';

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        parent::__construct(static::$defaultName);

        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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

        $this->driver->setupBroker(new ConsoleLogger($output));
    }
}
