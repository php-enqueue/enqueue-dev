<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class DbalSendPriorityMessagesTest extends TestCase
{
    public function test()
    {
        $context = $this->createContext();
        $queue = $this->createQueue($context, 'default');
        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertNull($consumer->receiveNoWait());

        $messagePriorities = [1, 0, 3];
        $producer = $context->createProducer();
        foreach ($messagePriorities as $priority) {
            $producer->send($queue, $this->createMessage($context, $priority));
        }

        sort($messagePriorities);
        foreach (array_reverse($messagePriorities) as $priority) {
            $message = $consumer->receive(8000); // 8 sec

            $this->assertInstanceOf(PsrMessage::class, $message);
            $consumer->acknowledge($message);
            $this->assertSame('priority'.$priority, $message->getBody());
        }
    }

    /**
     * @return PsrContext
     */
    protected function createContext()
    {
        $factory =  new DbalConnectionFactory(
            [
                'lazy' => true,
                'connection' => [
                    'dbname' => getenv('SYMFONY__DB__NAME'),
                    'user' => getenv('SYMFONY__DB__USER'),
                    'password' => getenv('SYMFONY__DB__PASSWORD'),
                    'host' => getenv('SYMFONY__DB__HOST'),
                    'port' => getenv('SYMFONY__DB__PORT'),
                    'driver' => getenv('SYMFONY__DB__DRIVER'),
                ]
            ]
        );

        return $factory->createContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalContext $context
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        $queue = $context->createQueue($queueName);
        $context->createDataBaseTable();

        return $queue;
    }

    /**
     * @param PsrContext $context
     * @param int $priority
     * @return DbalMessage
     */
    protected function createMessage(PsrContext $context, $priority)
    {
        /** @var DbalMessage $message */
        $message = $context->createMessage('priority'.$priority);
        $message->setPriority($priority);

        return $message;
    }
}
