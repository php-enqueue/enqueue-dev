<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\ProcessorRegistryInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurableConsumeCommand extends Command
{
    use LimitsExtensionsCommandTrait;
    use QueueConsumerOptionsCommandTrait;
    use ChooseLoggerCommandTrait;

    protected static $defaultName = 'enqueue:transport:consume';

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
    private $queueConsumerIdPattern;

    /**
     * @var string
     */
    private $processorRegistryIdPattern;

    public function __construct(
        ContainerInterface $container,
        string $defaultTransport,
        string $queueConsumerIdPattern = 'enqueue.transport.%s.queue_consumer',
        string $processorRegistryIdPattern = 'enqueue.transport.%s.processor_registry'
    ) {
        $this->container = $container;
        $this->defaultTransport = $defaultTransport;
        $this->queueConsumerIdPattern = $queueConsumerIdPattern;
        $this->processorRegistryIdPattern = $processorRegistryIdPattern;

        parent::__construct(static::$defaultName);
    }

    protected function configure(): void
    {
        $this->configureLimitsExtensions();
        $this->configureQueueConsumerOptions();
        $this->configureLoggerExtension();

        $this
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to explicitly set a queue to consume from '.
                'and a message processor service')
            ->addArgument('processor', InputArgument::REQUIRED, 'A message processor.')
            ->addArgument('queues', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'A queue to consume from', [])
            ->addOption('transport', 't', InputOption::VALUE_OPTIONAL, 'The transport to consume messages from.', $this->defaultTransport)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transport = $input->getOption('transport');

        try {
            $consumer = $this->getQueueConsumer($transport);
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(sprintf('Transport "%s" is not supported.', $transport), null, $e);
        }

        $this->setQueueConsumerOptions($consumer, $input);

        $processor = $this->getProcessorRegistry($transport)->get($input->getArgument('processor'));

        $queues = $input->getArgument('queues');
        if (empty($queues) && $processor instanceof QueueSubscriberInterface) {
            $queues = $processor::getSubscribedQueues();
        }

        if (empty($queues)) {
            throw new \LogicException(sprintf('The queue is not provided. The processor must implement "%s" interface and it must return not empty array of queues or a queue set using as a second argument.', QueueSubscriberInterface::class));
        }

        $extensions = $this->getLimitsExtensions($input, $output);

        if ($loggerExtension = $this->getLoggerExtension($input, $output)) {
            array_unshift($extensions, $loggerExtension);
        }

        foreach ($queues as $queue) {
            $consumer->bind($queue, $processor);
        }

        $consumer->consume(new ChainExtension($extensions));

        return 0;
    }

    private function getQueueConsumer(string $name): QueueConsumerInterface
    {
        return $this->container->get(sprintf($this->queueConsumerIdPattern, $name));
    }

    private function getProcessorRegistry(string $name): ProcessorRegistryInterface
    {
        return $this->container->get(sprintf($this->processorRegistryIdPattern, $name));
    }
}
