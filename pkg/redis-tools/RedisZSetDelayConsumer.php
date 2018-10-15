<?php

namespace Enqueue\RedisTools;

use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

class RedisZSetDelayConsumer implements PsrConsumer
{
    /**
     * @var RedisDestination
     */
    private $queue;

    /**
     * @var RedisContext
     */
    private $context;

    /**
     * @param RedisContext     $context
     * @param RedisDestination $queue
     */
    public function __construct(RedisContext $context, RedisDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisDestination
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisMessage|null
     */
    public function receive($timeout = 0)
    {
        return $this->receiveNoWait();
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisMessage|null
     */
    public function receiveNoWait()
    {
        while (false !== ($timestamp = $this->nextDelayedTimestamp())) {
            $this->enqueueDelayedMessagesForTimestamp($timestamp);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param RedisMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        // do nothing. redis transport always works in auto ack mode
    }

    /**
     * {@inheritdoc}
     *
     * @param RedisMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, RedisMessage::class);

        // do nothing on reject. redis transport always works in auto ack mode

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);
        }
    }

    /**
     * @return Redis
     */
    private function getRedis()
    {
        return $this->context->getRedis();
    }

    //TODO: refactor into a php generator
    private function nextDelayedTimestamp()
    {
        $at = time();

        //TODO:check zrange by score definition
        $items = $this->getRedis()->zrangebyscore('enqueue:'.$this->getQueue()->getTopicName().':delayed', '-inf', $at, 'LIMIT', 0, 1);

        if (!empty($items)) {
            return $items[0];
        }

        return false;
    }

    private function enqueueDelayedMessagesForTimestamp($timestamp)
    {
        $message = null;
        while ($message = $this->nextMessageForTimestamp($timestamp)) {
            $this->context->createProducer()->send($this->queue, $message);
        }
    }

    private function nextMessageForTimestamp($timestamp)
    {
        $queue = 'enqueue:'.$this->getQueue()->getTopicName().':delayed:'.$timestamp;
        if ($message = $this->getRedis()->rpop($queue)) {
            if (0 == $this->getRedis()->llen($queue)) {
                $this->getRedis()->del($queue);
                $this->getRedis()->zrem('enqueue:'.$this->getQueue()->getTopicName().':delayed', $timestamp);
            }

            return RedisMessage::jsonUnserialize($message);
        }
    }
}
