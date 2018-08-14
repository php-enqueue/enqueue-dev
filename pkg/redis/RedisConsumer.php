<?php

namespace Enqueue\Redis;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

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
        $timeout = (int) ($timeout / 1000);
        if (empty($timeout)) {
            while (true) {
                if ($message = $this->receive(5000)) {
                    return $message;
                }
            }
        }

        if ($result = $this->getRedis()->brpop([$this->queue->getName()], $timeout)) {
            return RedisMessage::jsonUnserialize($result->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisMessage|null
     */
    public function receiveNoWait()
    {
        if ($result = $this->getRedis()->rpop($this->queue->getName())) {
            return RedisMessage::jsonUnserialize($result->getMessage());
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
