<?php

namespace Enqueue\Symfony;

use Enqueue\Client\DriverInterface;
use Enqueue\SetupBroker\SetupBrokerInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBrokerCommand extends Command
{
    protected static $defaultName = 'enqueue:transport:setup-broker';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $defaultTransport;

    /**
     * @var string
     */
    private $setupBrokerIdPattern;

    public function __construct(ContainerInterface $container, string $defaultTransport, string $setupBrokerIdPattern = 'enqueue.transport.%s.setup_broker')
    {
        $this->container = $container;
        $this->defaultTransport = $defaultTransport;
        $this->setupBrokerIdPattern = $setupBrokerIdPattern;

        parent::__construct(static::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Setup broker. Configure the broker, creates queues, topics and so on.')
            ->addOption('transport', 't', InputOption::VALUE_OPTIONAL, 'The transport to consume messages from.', $this->defaultTransport)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $transport = $input->getOption('transport');

        $logger = new ConsoleLogger($output);

        try {
            $setupBroker = $this->getSetupBroker($transport);

            $logger->info(get_class($setupBroker));

            $setupBroker->setupBroker();
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(sprintf('Transport "%s" is not supported.', $transport), null, $e);
        }

        $output->writeln('Broker set up');

        return null;
    }

    private function getSetupBroker(string $transport): SetupBrokerInterface
    {
        return $this->container->get(sprintf($this->setupBrokerIdPattern, $transport));
    }
}
