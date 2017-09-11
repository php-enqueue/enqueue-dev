<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumer;
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
            ->addOption('idle-timeout', null, InputOption::VALUE_REQUIRED, 'The time in milliseconds queue consumer idle if no message has been received.')
            ->addOption('receive-timeout', null, InputOption::VALUE_REQUIRED, 'The time in milliseconds queue consumer waits for a message.')
        ;
    }

    /**
     * @param QueueConsumer  $consumer
     * @param InputInterface $input
     */
    protected function setQueueConsumerOptions(QueueConsumer $consumer, InputInterface $input)
    {
        if (null !== $idleTimeout = $input->getOption('idle-timeout')) {
            $consumer->setIdleTimeout((int) $idleTimeout);
        }

        if (null !== $receiveTimeout = $input->getOption('receive-timeout')) {
            $consumer->setReceiveTimeout((int) $receiveTimeout);
        }
    }
}
