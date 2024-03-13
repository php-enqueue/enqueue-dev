<?php

namespace Enqueue\AmqpExt;

use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class AmqpConsumer implements InteropAmqpConsumer
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var InteropAmqpQueue
     */
    private $queue;

    /**
     * @var \AMQPQueue
     */
    private $extQueue;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var string
     */
    private $consumerTag;

    public function __construct(AmqpContext $context, InteropAmqpQueue $queue)
    {
        $this->queue = $queue;
        $this->context = $context;
        $this->flags = self::FLAG_NOPARAM;
    }

    public function setConsumerTag(?string $consumerTag = null): void
    {
        $this->consumerTag = $consumerTag;
    }

    public function getConsumerTag(): ?string
    {
        return $this->consumerTag;
    }

    public function clearFlags(): void
    {
        $this->flags = self::FLAG_NOPARAM;
    }

    public function addFlag(int $flag): void
    {
        $this->flags |= $flag;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * @return InteropAmqpQueue
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @return InteropAmqpMessage
     */
    public function receive(int $timeout = 0): ?Message
    {
        $end = microtime(true) + ($timeout / 1000);

        while (0 === $timeout || microtime(true) < $end) {
            if ($message = $this->receiveNoWait()) {
                return $message;
            }

            usleep(100000); // 100ms
        }

        return null;
    }

    /**
     * @return InteropAmqpMessage
     */
    public function receiveNoWait(): ?Message
    {
        if ($extMessage = $this->getExtQueue()->get(Flags::convertConsumerFlags($this->flags))) {
            return $this->context->convertMessage($extMessage);
        }

        return null;
    }

    /**
     * @param InteropAmqpMessage $message
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $this->getExtQueue()->ack((int) $message->getDeliveryTag());
    }

    /**
     * @param InteropAmqpMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $this->getExtQueue()->reject(
            $message->getDeliveryTag(),
            $requeue ? \AMQP_REQUEUE : \AMQP_NOPARAM
        );
    }

    private function getExtQueue(): \AMQPQueue
    {
        if (false == $this->extQueue) {
            $extQueue = new \AMQPQueue($this->context->getExtChannel());
            $extQueue->setName($this->queue->getQueueName());
            $extQueue->setFlags(Flags::convertQueueFlags($this->queue->getFlags()));
            $extQueue->setArguments($this->queue->getArguments());

            $this->extQueue = $extQueue;
        }

        return $this->extQueue;
    }
}
