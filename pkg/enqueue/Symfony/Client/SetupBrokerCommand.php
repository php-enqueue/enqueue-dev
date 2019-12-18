<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBrokerCommand extends Command
{
    protected static $defaultName = 'enqueue:setup-broker';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $defaultClient;

    /**
     * @var string
     */
    private $driverIdPattern;

    public function __construct(ContainerInterface $container, string $defaultClient, string $driverIdPattern = 'enqueue.client.%s.driver')
    {
        $this->container = $container;
        $this->defaultClient = $defaultClient;
        $this->driverIdPattern = $driverIdPattern;

        parent::__construct(static::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['enq:sb'])
            ->setDescription('Setup broker. Configure the broker, creates queues, topics and so on.')
            ->addOption('client', 'c', InputOption::VALUE_OPTIONAL, 'The client to consume messages from.', $this->defaultClient)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $input->getOption('client');

        try {
            $this->getDriver($client)->setupBroker(new ConsoleLogger($output));
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(sprintf('Client "%s" is not supported.', $client), null, $e);
        }

        $output->writeln('Broker set up');

        return 0;
    }

    private function getDriver(string $client): DriverInterface
    {
        return $this->container->get(sprintf($this->driverIdPattern, $client));
    }
}
