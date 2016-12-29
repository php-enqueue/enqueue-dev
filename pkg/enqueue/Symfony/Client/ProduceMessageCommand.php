<?php
namespace Enqueue\Symfony\Client;

use Enqueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProduceMessageCommand extends Command
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @param MessageProducerInterface $producer
     */
    public function __construct(MessageProducerInterface $producer)
    {
        parent::__construct(null);

        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('enqueue:produce')
            ->setAliases(['enq:p'])
            ->setDescription('A command to send a message to topic')
            ->addArgument('topic', InputArgument::REQUIRED, 'A topic to send message to')
            ->addArgument('message', InputArgument::REQUIRED, 'A message to send')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->producer->send(
            $input->getArgument('topic'),
            $input->getArgument('message')
        );

        $output->writeln('Message is sent');
    }
}
