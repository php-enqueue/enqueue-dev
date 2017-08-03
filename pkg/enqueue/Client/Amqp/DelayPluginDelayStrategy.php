<?php

namespace Enqueue\Client\Amqp;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;

class DelayPluginDelayStrategy implements DelayStrategy
{
    public function delayMessage(AmqpContext $context, AmqpDestination $dest, AmqpMessage $message)
    {
        $delaySec = 1235; // $message->getDelay();

        if ($dest instanceof AmqpTopic) {
            $delayTopic = $context->createTopic($dest->getTopicName().'.x.delayed');
            $delayTopic->setType('x-delayed-message');
            $delayTopic->addFlag(AmqpTopic::FLAG_DURABLE);
            $delayTopic->setArgument('x-delayed-type', AmqpTopic::TYPE_DIRECT);

            $context->declareTopic($delayTopic);
            $context->bind(new AmqpBind($dest, $delayTopic));
        } elseif ($dest instanceof AmqpQueue) {
            $delayTopic = $context->createTopic($dest->getQueueName().'.delayed');
            $delayTopic->setType('x-delayed-message');
            $delayTopic->addFlag(AmqpTopic::FLAG_DURABLE);
            $delayTopic->setArgument('x-delayed-type', AmqpTopic::TYPE_DIRECT);

            $context->declareTopic($delayTopic);
            $context->bind(new AmqpBind($delayTopic, $dest, $dest->getQueueName()));
        } else {
            throw new \LogicException();
        }

        $delayMessage = $context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $delayMessage->setProperty('x-delay', (string) ($delaySec * 1000));

        $context->createProducer()->send($delayTopic, $delayMessage);
    }
}
