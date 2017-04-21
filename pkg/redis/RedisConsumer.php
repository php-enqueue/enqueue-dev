<?php

namespace Enqueue\Redis;

use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrMessage;

class RedisConsumer implements PsrConsumer
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
     * @param RedisContext $context
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
        if ($message = $this->getRedis()->brpop($this->queue->getName(), (int) $timeout / 1000)) {
            return RedisMessage::jsonUnserialize($message);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisMessage|null
     */
    public function receiveNoWait()
    {
        if ($message = $this->getRedis()->rpop($this->queue->getName())) {
            return RedisMessage::jsonUnserialize($message);
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
}
