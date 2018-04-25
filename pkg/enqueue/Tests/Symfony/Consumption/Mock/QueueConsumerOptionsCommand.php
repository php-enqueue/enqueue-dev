<?php

namespace Enqueue\Tests\Symfony\Consumption\Mock;

use Enqueue\Consumption\QueueConsumer;
use Enqueue\Symfony\Consumption\QueueConsumerOptionsCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueConsumerOptionsCommand extends Command
{
    use QueueConsumerOptionsCommandTrait;

    /**
     * @var QueueConsumer
     */
    private $consumer;

    public function __construct(QueueConsumer $consumer)
    {
        parent::__construct('queue-consumer-options');

        $this->consumer = $consumer;
    }

    protected function configure()
    {
        parent::configure();

        $this->configureQueueConsumerOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setQueueConsumerOptions($this->consumer, $input);
    }
}
