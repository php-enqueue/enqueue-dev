<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProduceCommand extends Command
{
    protected static $defaultName = 'enqueue:produce';

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
    private $producerIdPattern;

    public function __construct(ContainerInterface $container, string $defaultClient, string $producerIdPattern = 'enqueue.client.%s.producer')
    {
        $this->container = $container;
        $this->defaultClient = $defaultClient;
        $this->producerIdPattern = $producerIdPattern;

        parent::__construct(static::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Sends an event to the topic')
            ->addArgument('message', InputArgument::REQUIRED, 'A message')
            ->addOption('header', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The message headers')
            ->addOption('client', 'c', InputOption::VALUE_OPTIONAL, 'The client to consume messages from.', $this->defaultClient)
            ->addOption('topic', null, InputOption::VALUE_OPTIONAL, 'The topic to send a message to')
            ->addOption('command', null, InputOption::VALUE_OPTIONAL, 'The command to send a message to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $topic = $input->getOption('topic');
        $command = $input->getOption('command');
        $message = $input->getArgument('message');
        $headers = (array) $input->getOption('header');
        $client = $input->getOption('client');

        if ($topic && $command) {
            throw new \LogicException('Either topic or command option should be set, both are set.');
        }

        try {
            $producer = $this->getProducer($client);
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(sprintf('Client "%s" is not supported.', $client), null, $e);
        }

        if ($topic) {
            $producer->sendEvent($topic, new Message($message, [], $headers));

            $output->writeln('An event is sent');
        } elseif ($command) {
            $producer->sendCommand($command, $message);

            $output->writeln('A command is sent');
        } else {
            throw new \LogicException('Either topic or command option should be set, none is set.');
        }

        return 0;
    }

    private function getProducer(string $client): ProducerInterface
    {
        return $this->container->get(sprintf($this->producerIdPattern, $client));
    }
}
