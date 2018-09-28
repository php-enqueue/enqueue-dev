<?php

declare(strict_types=1);

namespace Enqueue\AmqpBunny;

use Bunny\Channel;
use Bunny\Message as BunnyMessage;
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
     * @var Channel
     */
    private $channel;

    /**
     * @var InteropAmqpQueue
     */
    private $queue;

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
        $this->context = $context;
        $this->channel = $context->getBunnyChannel();
        $this->queue = $queue;
        $this->flags = self::FLAG_NOPARAM;
    }

    public function setConsumerTag(string $consumerTag = null): void
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

            usleep(100000); //100ms
        }

        return null;
    }

    /**
     * @return InteropAmqpMessage
     */
    public function receiveNoWait(): ?Message
    {
        if ($message = $this->channel->get($this->queue->getQueueName(), (bool) ($this->getFlags() & InteropAmqpConsumer::FLAG_NOACK))) {
            return $this->context->convertMessage($message);
        }

        return null;
    }

    /**
     * @param InteropAmqpMessage $message
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $bunnyMessage = new BunnyMessage('', $message->getDeliveryTag(), '', '', '', [], '');
        $this->channel->ack($bunnyMessage);
    }

    /**
     * @param InteropAmqpMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $bunnyMessage = new BunnyMessage('', $message->getDeliveryTag(), '', '', '', [], '');
        $this->channel->reject($bunnyMessage, $requeue);
    }
}
