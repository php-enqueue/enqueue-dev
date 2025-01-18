<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait QueueConsumerOptionsCommandTrait
{
    protected function configureQueueConsumerOptions()
    {
        $this
            ->addOption('receive-timeout', null, InputOption::VALUE_REQUIRED, 'The time in milliseconds queue consumer waits for a message.')
        ;
    }

    protected function setQueueConsumerOptions(QueueConsumerInterface $consumer, InputInterface $input)
    {
        if (null !== $receiveTimeout = $input->getOption('receive-timeout')) {
            $consumer->setReceiveTimeout((int) $receiveTimeout);
        }
    }
}
