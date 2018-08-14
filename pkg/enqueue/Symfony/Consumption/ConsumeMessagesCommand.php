<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\QueueConsumerInterface;
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

    /**
     * @var QueueConsumerInterface
     */
    protected $consumer;

    /**
     * @param QueueConsumerInterface $consumer
     */
    public function __construct(QueueConsumerInterface $consumer)
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
