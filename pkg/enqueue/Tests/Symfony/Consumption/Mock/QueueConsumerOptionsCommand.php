<?php

namespace Enqueue\Tests\Symfony\Consumption\Mock;

use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Symfony\Consumption\QueueConsumerOptionsCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueConsumerOptionsCommand extends Command
{
    use QueueConsumerOptionsCommandTrait;

    /**
     * @var QueueConsumerInterface
     */
    private $consumer;

    public function __construct(QueueConsumerInterface $consumer)
    {
        parent::__construct('queue-consumer-options');

        $this->consumer = $consumer;
    }

    protected function configure()
    {
        parent::configure();

        $this->configureQueueConsumerOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setQueueConsumerOptions($this->consumer, $input);

        return 0;
    }
}
