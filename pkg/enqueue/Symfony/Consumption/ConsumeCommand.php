<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Consumption\QueueConsumerRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ConsumeCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use LimitsExtensionsCommandTrait;
    use QueueConsumerOptionsCommandTrait;

    protected static $defaultName = 'enqueue:transport:consume';

    /**
     * @var QueueConsumerRegistryInterface
     */
    protected $consumerRegistry;

    /**
     * [name => QueueConsumerInterface].
     *
     * @param QueueConsumerInterface[]
     */
    public function __construct(QueueConsumerRegistryInterface $consumerRegistry)
    {
        parent::__construct(static::$defaultName);

        $this->consumerRegistry = $consumerRegistry;
    }

    protected function configure(): void
    {
        $this->configureLimitsExtensions();
        $this->configureQueueConsumerOptions();

        $this
            ->addOption('transport', 't', InputOption::VALUE_OPTIONAL, 'The transport to consume messages from.', 'default')
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to configure queue consumer before adding to the command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // QueueConsumer must be pre configured outside of the command!
        $consumer = $this->consumerRegistry->get($input->getOption('transport'));

        $this->setQueueConsumerOptions($consumer, $input);

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, new LoggerExtension(new ConsoleLogger($output)));

        $runtimeExtensions = new ChainExtension($extensions);

        $consumer->consume($runtimeExtensions);

        return null;
    }
}
