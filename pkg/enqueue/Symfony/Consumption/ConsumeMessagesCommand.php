<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\QueueConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ConsumeMessagesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use LimitsExtensionsCommandTrait;
    use QueueConsumerOptionsCommandTrait;

    protected static $defaultName = 'enqueue:transport:consume';

    /**
     * @var QueueConsumer
     */
    protected $consumer;

    /**
     * @param QueueConsumer $consumer
     */
    public function __construct(QueueConsumer $consumer)
    {
        parent::__construct(static::$defaultName);

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
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to configure queue consumer before adding to the command')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setQueueConsumerOptions($this->consumer, $input);

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, new LoggerExtension(new ConsoleLogger($output)));

        $runtimeExtensions = new ChainExtension($extensions);

        $this->consumer->consume($runtimeExtensions);
    }
}
