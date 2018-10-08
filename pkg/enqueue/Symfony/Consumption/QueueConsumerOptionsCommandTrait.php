<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait QueueConsumerOptionsCommandTrait
{
    /**
     * {@inheritdoc}
     */
    protected function configureQueueConsumerOptions()
    {
        $this
            ->addOption('receive-timeout', null, InputOption::VALUE_REQUIRED, 'The time in milliseconds queue consumer waits for a message.')
        ;
    }

    /**
     * @param QueueConsumerInterface $consumer
     * @param InputInterface         $input
     */
    protected function setQueueConsumerOptions(QueueConsumerInterface $consumer, InputInterface $input)
    {
        if (null !== $receiveTimeout = $input->getOption('receive-timeout')) {
            $consumer->setReceiveTimeout((int) $receiveTimeout);
        }
    }
}
