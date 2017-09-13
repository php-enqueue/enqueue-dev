<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendAndReceivePriorityMessagesFromQueueSpec;

/**
 * @group functional
 */
class DbalSendAndReceivePriorityMessagesFromQueueTest extends SendAndReceivePriorityMessagesFromQueueSpec
{
    /**
     * @return PsrContext
     */
    protected function createContext()
    {
        $factory = new DbalConnectionFactory([
            'lazy' => true,
            'connection' => [
                'dbname' => getenv('SYMFONY__DB__NAME'),
                'user' => getenv('SYMFONY__DB__USER'),
                'password' => getenv('SYMFONY__DB__PASSWORD'),
                'host' => getenv('SYMFONY__DB__HOST'),
                'port' => getenv('SYMFONY__DB__PORT'),
                'driver' => getenv('SYMFONY__DB__DRIVER'),
            ],
        ]);

        $context = $factory->createContext();
        $context->createDataBaseTable();

        return $context;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalMessage
     */
    protected function createMessage(PsrContext $context, $priority)
    {
        /** @var DbalMessage $message */
        $message = $context->createMessage('priority'.$priority);
        $message->setPriority($priority);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalDestination
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        return parent::createQueue($context, $queueName.time());
    }
}
