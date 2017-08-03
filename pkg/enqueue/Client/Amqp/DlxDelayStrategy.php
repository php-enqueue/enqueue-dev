<?php

namespace Enqueue\Client\Amqp;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;

class DlxDelayStrategy implements DelayStrategy
{
    /**
     * {@inheritdoc}
     */
    public function delayMessage(AmqpContext $context, AmqpDestination $dest, AmqpMessage $message)
    {
        $delaySec = 1235; // $message->getDelay();

        if ($dest instanceof AmqpTopic) {
            $delayQueue = $context->createQueue($dest->getTopicName().'.'.$delaySec.'.x.delayed');
            $delayQueue->addFlag(AmqpTopic::FLAG_DURABLE);
            $delayQueue->setArgument('x-dead-letter-exchange', $dest->getTopicName());
        } elseif ($dest instanceof AmqpQueue) {
            $delayQueue = $context->createQueue($dest->getQueueName().'.'.$delaySec.'.delayed');
            $delayQueue->addFlag(AmqpTopic::FLAG_DURABLE);
            $delayQueue->setArgument('x-dead-letter-exchange', '');
            $delayQueue->setArgument('x-dead-letter-routing-key', $dest->getQueueName());
        } else {
            throw new \LogicException();
        }

        $context->declareQueue($delayQueue);

        $properties = $message->getProperties();

        // The x-death header must be removed because of the bug in RabbitMQ.
        // It was reported that the bug is fixed since 3.5.4 but I tried with 3.6.1 and the bug still there.
        // https://github.com/rabbitmq/rabbitmq-server/issues/216
        unset($properties['x-death']);

        $delayMessage = $context->createMessage($message->getBody(), $properties, $message->getHeaders());
        $delayMessage->setExpiration((string) ($delaySec * 1000));

        $context->createProducer()->send($delayQueue, $delayMessage);
    }
}
