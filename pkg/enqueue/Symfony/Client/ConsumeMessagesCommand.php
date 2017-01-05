<?php
namespace Enqueue\Symfony\Client;

use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Symfony\Consumption\LimitsExtensionsCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;
    use SetupBrokerExtensionCommandTrait;

    /**
     * @var QueueConsumer
     */
    private $consumer;

    /**
     * @var DelegateProcessor
     */
    private $processor;

    /**
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param QueueConsumer     $consumer
     * @param DelegateProcessor $processor
     * @param QueueMetaRegistry $queueMetaRegistry
     * @param DriverInterface   $driver
     */
    public function __construct(
        QueueConsumer $consumer,
        DelegateProcessor $processor,
        QueueMetaRegistry $queueMetaRegistry,
        DriverInterface $driver
    ) {
        parent::__construct(null);

        $this->consumer = $consumer;
        $this->processor = $processor;
        $this->queueMetaRegistry = $queueMetaRegistry;
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureLimitsExtensions();
        $this->configureSetupBrokerExtension();

        $this
            ->setName('enqueue:consume')
            ->setAliases(['enq:c'])
            ->setDescription('A client\'s worker that processes messages. '.
                'By default it connects to default queue. '.
                'It select an appropriate message processor based on a message headers')
            ->addArgument('client-queue-names', InputArgument::IS_ARRAY, 'Queues to consume messages from')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueMetas = [];
        if ($clientQueueNames = $input->getArgument('client-queue-names')) {
            foreach ($clientQueueNames as $clientQueueName) {
                $queueMetas[] = $this->queueMetaRegistry->getQueueMeta($clientQueueName);
            }
        } else {
            $queueMetas = $this->queueMetaRegistry->getQueuesMeta();
        }

        foreach ($queueMetas as $queueMeta) {
            $queue = $this->driver->createQueue($queueMeta->getClientName());
            $this->consumer->bind($queue, $this->processor);
        }

        try {
            $this->consumer->consume($this->getRuntimeExtensions($input, $output));
        } finally {
            $this->consumer->getPsrContext()->close();
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return ChainExtension
     */
    protected function getRuntimeExtensions(InputInterface $input, OutputInterface $output)
    {
        $extensions = [new LoggerExtension(new ConsoleLogger($output))];
        $extensions = array_merge($extensions, $this->getLimitsExtensions($input, $output));

        if ($setupBrokerExtension = $this->getSetupBrokerExtension($input, $this->driver)) {
            $extensions[] = $setupBrokerExtension;
        }

        return new ChainExtension($extensions);
    }
}
