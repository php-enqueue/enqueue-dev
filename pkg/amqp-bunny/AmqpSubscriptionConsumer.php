<?php

namespace Enqueue\AmqpBunny;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Exception\ClientException;
use Bunny\Message;
use Enqueue\AmqpTools\SignalSocketHelper;
use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Queue\Exception;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrSubscriptionConsumer;

class AmqpSubscriptionConsumer implements PsrSubscriptionConsumer
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * an item contains an array: [AmqpConsumerInterop $consumer, callable $callback];.
     *
     * @var array
     */
    private $subscribers;

    public function __construct(AmqpContext $context)
    {
        $this->context = $context;

        $this->subscribers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function consume($timeout = 0)
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('There is no subscribers. Consider calling basicConsumeSubscribe before consuming');
        }

        $signalHandler = new SignalSocketHelper();
        $signalHandler->beforeSocket();

        try {
            $this->context->getBunnyChannel()->getClient()->run(0 !== $timeout ? $timeout / 1000 : null);
        } catch (ClientException $e) {
            if ('stream_select() failed.' == $e->getMessage() && $signalHandler->wasThereSignal()) {
                return;
            }

            throw $e;
        } finally {
            $signalHandler->afterSocket();
        }
    }

    /**
     * @param AmqpConsumer $consumer
     *
     * {@inheritdoc}
     */
    public function subscribe(PsrConsumer $consumer, callable $callback)
    {
        if (false == $consumer instanceof AmqpConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', AmqpConsumer::class, get_class($consumer)));
        }

        if ($consumer->getConsumerTag() && array_key_exists($consumer->getConsumerTag(), $this->subscribers)) {
            return;
        }

        $bunnyCallback = function (Message $message, Channel $channel, Client $bunny) {
            $receivedMessage = $this->context->convertMessage($message);
            $receivedMessage->setConsumerTag($message->consumerTag);

            /**
             * @var AmqpConsumer
             * @var callable     $callback
             */
            list($consumer, $callback) = $this->subscribers[$message->consumerTag];

            if (false === call_user_func($callback, $receivedMessage, $consumer)) {
                $bunny->stop();
            }
        };

        $frame = $this->context->getBunnyChannel()->consume(
            $bunnyCallback,
            $consumer->getQueue()->getQueueName(),
            $consumer->getConsumerTag(),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOLOCAL),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOACK),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_EXCLUSIVE),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOWAIT)
        );

        if (empty($frame->consumerTag)) {
            throw new Exception('Got empty consumer tag');
        }

        $consumer->setConsumerTag($frame->consumerTag);

        $this->subscribers[$frame->consumerTag] = [$consumer, $callback];
    }

    /**
     * @param AmqpConsumer $consumer
     *
     * {@inheritdoc}
     */
    public function unsubscribe(PsrConsumer $consumer)
    {
        if (false == $consumer instanceof AmqpConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', AmqpConsumer::class, get_class($consumer)));
        }

        if (false == $consumer->getConsumerTag()) {
            return;
        }

        $consumerTag = $consumer->getConsumerTag();

        $this->context->getBunnyChannel()->cancel($consumerTag);
        $consumer->setConsumerTag(null);
        unset($this->subscribers[$consumerTag]);
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeAll()
    {
        foreach ($this->subscribers as list($consumer)) {
            $this->unsubscribe($consumer);
        }
    }
}
