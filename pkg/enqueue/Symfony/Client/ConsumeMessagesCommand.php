<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Symfony\Consumption\LimitsExtensionsCommandTrait;
use Enqueue\Symfony\Consumption\QueueConsumerOptionsCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;
    use SetupBrokerExtensionCommandTrait;
    use QueueConsumerOptionsCommandTrait;

    protected static $defaultName = 'enqueue:consume';

    /**
     * @var QueueConsumerInterface
     */
    private $consumer;

    /**
     * @var DelegateProcessor
     */
    private $processor;

    /**
     * @var DriverInterface
     */
    private $driver;

    public function __construct(
        QueueConsumerInterface $consumer,
        DelegateProcessor $processor,
        DriverInterface $driver
    ) {
        parent::__construct(static::$defaultName);

        $this->consumer = $consumer;
        $this->processor = $processor;
        $this->driver = $driver;
    }

    protected function configure(): void
    {
        $this->configureLimitsExtensions();
        $this->configureSetupBrokerExtension();
        $this->configureQueueConsumerOptions();

        $this
            ->setAliases(['enq:c'])
            ->setDescription('A client\'s worker that processes messages. '.
                'By default it connects to default queue. '.
                'It select an appropriate message processor based on a message headers')
            ->addArgument('client-queue-names', InputArgument::IS_ARRAY, 'Queues to consume messages from')
            ->addOption('skip', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Queues to skip consumption of messages from', [])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->setQueueConsumerOptions($this->consumer, $input);

        $clientQueueNames = $input->getArgument('client-queue-names');
        if (empty($clientQueueNames)) {
            $clientQueueNames[$this->driver->getConfig()->getDefaultProcessorQueueName()] = true;
            $clientQueueNames[$this->driver->getConfig()->getRouterQueueName()] = true;

            foreach ($this->driver->getRouteCollection()->all() as $route) {
                if ($route->getQueue()) {
                    $clientQueueNames[$route->getQueue()] = true;
                }
            }

            foreach ($input->getOption('skip') as $skipClientQueueName) {
                unset($clientQueueNames[$skipClientQueueName]);
            }

            $clientQueueNames = array_keys($clientQueueNames);
        }

        foreach ($clientQueueNames as $clientQueueName) {
            $queue = $this->driver->createQueue($clientQueueName);
            $this->consumer->bind($queue, $this->processor);
        }

        $this->consumer->consume($this->getRuntimeExtensions($input, $output));

        return null;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return ChainExtension
     */
    protected function getRuntimeExtensions(InputInterface $input, OutputInterface $output): ExtensionInterface
    {
        $extensions = [new LoggerExtension(new ConsoleLogger($output))];
        $extensions = array_merge($extensions, $this->getLimitsExtensions($input, $output));

        if ($setupBrokerExtension = $this->getSetupBrokerExtension($input, $this->driver)) {
            $extensions[] = $setupBrokerExtension;
        }

        return new ChainExtension($extensions);
    }
}
