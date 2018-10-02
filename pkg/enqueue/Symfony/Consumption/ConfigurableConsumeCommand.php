<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\QueueConsumerRegistryInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\ProcessorRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurableConsumeCommand extends Command
{
    use LimitsExtensionsCommandTrait;
    use QueueConsumerOptionsCommandTrait;

    protected static $defaultName = 'enqueue:transport:consume';

    /**
     * @var QueueConsumerRegistryInterface
     */
    protected $consumerRegistry;

    /**
     * @var ProcessorRegistryInterface
     */
    private $processorRegistry;

    public function __construct(QueueConsumerRegistryInterface $consumerRegistry, ProcessorRegistryInterface $processorRegistry)
    {
        parent::__construct(static::$defaultName);

        $this->consumerRegistry = $consumerRegistry;
        $this->processorRegistry = $processorRegistry;
    }

    protected function configure(): void
    {
        $this->configureLimitsExtensions();
        $this->configureQueueConsumerOptions();

        $this
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to explicitly set a queue to consume from '.
                'and a message processor service')
            ->addArgument('processor', InputArgument::REQUIRED, 'A message processor.')
            ->addArgument('queues', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'A queue to consume from', [])
            ->addOption('transport', 't', InputOption::VALUE_OPTIONAL, 'The transport to consume messages from.', 'default')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $consumer = $this->consumerRegistry->get($input->getOption('transport'));

        $this->setQueueConsumerOptions($consumer, $input);

        $processor = $this->processorRegistry->get($input->getArgument('processor'));

        $queues = $input->getArgument('queues');
        if (empty($queues) && $processor instanceof QueueSubscriberInterface) {
            $queues = $processor::getSubscribedQueues();
        }

        if (empty($queues)) {
            throw new \LogicException(sprintf(
                'The queue is not provided. The processor must implement "%s" interface and it must return not empty array of queues or a queue set using as a second argument.',
                QueueSubscriberInterface::class
            ));
        }

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, new LoggerExtension(new ConsoleLogger($output)));

        $runtimeExtensions = new ChainExtension($extensions);

        foreach ($queues as $queue) {
            $consumer->bind($queue, $processor);
        }

        $consumer->consume($runtimeExtensions);

        return null;
    }
}
