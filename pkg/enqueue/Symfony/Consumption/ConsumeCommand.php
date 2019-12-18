<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\ExitStatusExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends Command
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

    public function __construct(ContainerInterface $container, string $defaultTransport, string $queueConsumerIdPattern = 'enqueue.transport.%s.queue_consumer')
    {
        $this->container = $container;
        $this->defaultTransport = $defaultTransport;
        $this->queueConsumerIdPattern = $queueConsumerIdPattern;

        parent::__construct(static::$defaultName);
    }

    protected function configure(): void
    {
        $this->configureLimitsExtensions();
        $this->configureQueueConsumerOptions();
        $this->configureLoggerExtension();

        $this
            ->addOption('transport', 't', InputOption::VALUE_OPTIONAL, 'The transport to consume messages from.', $this->defaultTransport)
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to configure queue consumer before adding to the command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transport = $input->getOption('transport');

        try {
            // QueueConsumer must be pre configured outside of the command!
            $consumer = $this->getQueueConsumer($transport);
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(sprintf('Transport "%s" is not supported.', $transport), null, $e);
        }

        $this->setQueueConsumerOptions($consumer, $input);

        $extensions = $this->getLimitsExtensions($input, $output);

        if ($loggerExtension = $this->getLoggerExtension($input, $output)) {
            array_unshift($extensions, $loggerExtension);
        }

        $exitStatusExtension = new ExitStatusExtension();
        array_unshift($extensions, $exitStatusExtension);

        $consumer->consume(new ChainExtension($extensions));

        return $exitStatusExtension->getExitStatus() ?? 0;
    }

    private function getQueueConsumer(string $name): QueueConsumerInterface
    {
        return $this->container->get(sprintf($this->queueConsumerIdPattern, $name));
    }
}
