<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\QueueSubscriberInterface;
use Interop\Queue\PsrProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ContainerAwareConsumeMessagesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use LimitsExtensionsCommandTrait;
    use QueueConsumerOptionsCommandTrait;

    /**
     * @var QueueConsumer
     */
    protected $consumer;

    /**
     * ConsumeMessagesCommand constructor.
     *
     * @param QueueConsumer $consumer
     */
    public function __construct(QueueConsumer $consumer)
    {
        parent::__construct(null);

        $this->consumer = $consumer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureLimitsExtensions();
        $this->configureQueueConsumerOptions();

        $this
            ->setName('enqueue:transport:consume')
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to explicitly set a queue to consume from '.
                'and a message processor service')
            ->addArgument('processor-service', InputArgument::REQUIRED, 'A message processor service')
            ->addOption('queue', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Queues to consume from', [])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setQueueConsumerOptions($this->consumer, $input);

        /** @var PsrProcessor $processor */
        $processor = $this->container->get($input->getArgument('processor-service'));
        if (false == $processor instanceof  PsrProcessor) {
            throw new \LogicException(sprintf(
                'Invalid message processor service given. It must be an instance of %s but %s',
                PsrProcessor::class,
                get_class($processor)
            ));
        }

        $queues = $input->getOption('queue');
        if (empty($queues) && $processor instanceof QueueSubscriberInterface) {
            $queues = $processor::getSubscribedQueues();
        }

        if (empty($queues)) {
            throw new \LogicException(sprintf(
                'The queues are not provided. The processor must implement "%s" interface and it must return not empty array of queues or queues set using --queue option.',
                QueueSubscriberInterface::class
            ));
        }

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, new LoggerExtension(new ConsoleLogger($output)));

        $runtimeExtensions = new ChainExtension($extensions);

        foreach ($queues as $queue) {
            $this->consumer->bind($queue, $processor);
        }

        $this->consumer->consume($runtimeExtensions);
    }
}
