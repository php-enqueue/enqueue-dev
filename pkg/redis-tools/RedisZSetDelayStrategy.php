<?php

namespace Enqueue\RedisTools;

use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;

class RedisZSetDelayStrategy implements DelayStrategy
{
    /**
     * {@inheritdoc}
     */
    public function delayMessage(RedisContext $context, RedisDestination $dest, RedisMessage $message, $delayMsec)
    {
        $delayMessage = $context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $delayMessage->setProperty('x-delay', (int) $delayMsec);

        $targetTimestamp = time() + $delayMsec / 1000;

        $context->getRedis()->zadd('enqueue:'.$dest->getTopicName().':delayed', $targetTimestamp, $targetTimestamp);

        $delayTopic = $context->createTopic('enqueue:'.$dest->getTopicName().':delayed:'.$targetTimestamp);

        $producer = $context->createProducer();

        if ($producer instanceof DelayStrategyAware) {
            $producer->setDelayStrategy(null);
        }

        $producer->send($delayTopic, $delayMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function processDelayedMessage(RedisContext $context, RedisDestination $dest)
    {
        $delayConsumer = new RedisZSetDelayConsumer($context, $dest);

        $delayConsumer->receive();
    }
}
