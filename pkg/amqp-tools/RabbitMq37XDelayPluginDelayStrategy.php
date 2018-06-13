<?php

namespace Enqueue\AmqpTools;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;

class RabbitMq37XDelayPluginDelayStrategy extends RabbitMqDelayPluginDelayStrategy
{
    protected function buildDelayMessage(AmqpContext $context, AmqpMessage $message, int $delayMsec): AmqpMessage
    {
        $delayMessage = $context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $delayMessage->setHeader('x-delay', $delayMsec);
        $delayMessage->setRoutingKey($message->getRoutingKey());

        return $delayMessage;
    }
}
