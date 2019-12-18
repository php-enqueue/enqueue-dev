<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\ExitStatusExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Symfony\Consumption\ChooseLoggerCommandTrait;
use Enqueue\Symfony\Consumption\LimitsExtensionsCommandTrait;
use Enqueue\Symfony\Consumption\QueueConsumerOptionsCommandTrait;
use Interop\Queue\Processor;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends Command
{
    use LimitsExtensionsCommandTrait;
    use SetupBrokerExtensionCommandTrait;
    use QueueConsumerOptionsCommandTrait;
    use ChooseLoggerCommandTrait;

    protected static $defaultName = 'enqueue:consume';

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
    private $queueConsumerIdPattern;

    /**
     * @var string
     */
    private $driverIdPattern;

    /**
     * @var string
     */
    private $processorIdPattern;

    public function __construct(
        ContainerInterface $container,
        string $defaultClient,
        string $queueConsumerIdPattern = 'enqueue.client.%s.queue_consumer',
        string $driverIdPattern = 'enqueue.client.%s.driver',
        string $processorIdPatter = 'enqueue.client.%s.delegate_processor'
    ) {
        $this->container = $container;
        $this->defaultClient = $defaultClient;
        $this->queueConsumerIdPattern = $queueConsumerIdPattern;
        $this->driverIdPattern = $driverIdPattern;
        $this->processorIdPattern = $processorIdPatter;

        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->configureLimitsExtensions();
        $this->configureSetupBrokerExtension();
        $this->configureQueueConsumerOptions();
        $this->configureLoggerExtension();

        $this
            ->setAliases(['enq:c'])
            ->setDescription('A client\'s worker that processes messages. '.
                'By default it connects to default queue. '.
                'It select an appropriate message processor based on a message headers')
            ->addArgument('client-queue-names', InputArgument::IS_ARRAY, 'Queues to consume messages from')
            ->addOption('skip', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Queues to skip consumption of messages from', [])
            ->addOption('client', 'c', InputOption::VALUE_OPTIONAL, 'The client to consume messages from.', $this->defaultClient)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $input->getOption('client');

        try {
            $consumer = $this->getQueueConsumer($client);
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(sprintf('Client "%s" is not supported.', $client), null, $e);
        }

        $driver = $this->getDriver($client);
        $processor = $this->getProcessor($client);

        $this->setQueueConsumerOptions($consumer, $input);

        $allQueues[$driver->getConfig()->getDefaultQueue()] = true;
        $allQueues[$driver->getConfig()->getRouterQueue()] = true;
        foreach ($driver->getRouteCollection()->all() as $route) {
            if (false == $route->getQueue()) {
                continue;
            }
            if ($route->isProcessorExternal()) {
                continue;
            }

            $allQueues[$route->getQueue()] = $route->isPrefixQueue();
        }

        $selectedQueues = $input->getArgument('client-queue-names');
        if (empty($selectedQueues)) {
            $queues = $allQueues;
        } else {
            $queues = [];
            foreach ($selectedQueues as $queue) {
                if (false == array_key_exists($queue, $allQueues)) {
                    throw new \LogicException(sprintf('There is no such queue "%s". Available are "%s"', $queue, implode('", "', array_keys($allQueues))));
                }

                $queues[$queue] = $allQueues[$queue];
            }
        }

        foreach ($input->getOption('skip') as $skipQueue) {
            unset($queues[$skipQueue]);
        }

        foreach ($queues as $queue => $prefix) {
            $queue = $driver->createQueue($queue, $prefix);
            $consumer->bind($queue, $processor);
        }

        $runtimeExtensionChain = $this->getRuntimeExtensions($input, $output);
        $exitStatusExtension = new ExitStatusExtension();

        $consumer->consume(new ChainExtension([$runtimeExtensionChain, $exitStatusExtension]));

        return $exitStatusExtension->getExitStatus() ?? 0;
    }

    protected function getRuntimeExtensions(InputInterface $input, OutputInterface $output): ExtensionInterface
    {
        $extensions = [];
        $extensions = array_merge($extensions, $this->getLimitsExtensions($input, $output));

        $driver = $this->getDriver($input->getOption('client'));

        if ($setupBrokerExtension = $this->getSetupBrokerExtension($input, $driver)) {
            $extensions[] = $setupBrokerExtension;
        }

        if ($loggerExtension = $this->getLoggerExtension($input, $output)) {
            array_unshift($extensions, $loggerExtension);
        }

        return new ChainExtension($extensions);
    }

    private function getDriver(string $name): DriverInterface
    {
        return $this->container->get(sprintf($this->driverIdPattern, $name));
    }

    private function getQueueConsumer(string $name): QueueConsumerInterface
    {
        return $this->container->get(sprintf($this->queueConsumerIdPattern, $name));
    }

    private function getProcessor(string $name): Processor
    {
        return $this->container->get(sprintf($this->processorIdPattern, $name));
    }
}
