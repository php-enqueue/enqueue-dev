<?php

namespace Enqueue\AmqpExt;

use Interop\Amqp\AmqpSubscriptionConsumer as InteropAmqpSubscriptionConsumer;
use Interop\Queue\PsrConsumer;

class AmqpSubscriptionConsumer implements InteropAmqpSubscriptionConsumer
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
        if (false == (version_compare(phpversion('amqp'), '1.9.1', '>=') || '1.9.1-dev' == phpversion('amqp'))) {
            // @see https://github.com/php-enqueue/enqueue-dev/issues/110 and https://github.com/pdezwart/php-amqp/issues/281
            throw new \LogicException('The AMQP extension "basic_consume" method does not work properly prior 1.9.1 version.');
        }

        $this->context = $context;

        $this->subscribers = [];
    }

    public function consume(int $timeout = 0): void
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('There is no subscribers. Consider calling basicConsumeSubscribe before consuming');
        }

        /** @var \AMQPQueue $extQueue */
        $extConnection = $this->context->getExtChannel()->getConnection();

        $originalTimeout = $extConnection->getReadTimeout();
        try {
            $extConnection->setReadTimeout($timeout / 1000);

            reset($this->subscribers);
            /** @var $consumer AmqpConsumer */
            list($consumer) = current($this->subscribers);

            $extQueue = new \AMQPQueue($this->context->getExtChannel());
            $extQueue->setName($consumer->getQueue()->getQueueName());
            $extQueue->consume(function (\AMQPEnvelope $extEnvelope, \AMQPQueue $q) use ($originalTimeout, $extConnection) {
                $consumeTimeout = $extConnection->getReadTimeout();
                try {
                    $extConnection->setReadTimeout($originalTimeout);

                    $message = $this->context->convertMessage($extEnvelope);
                    $message->setConsumerTag($q->getConsumerTag());

                    /**
                     * @var AmqpConsumer
                     * @var callable     $callback
                     */
                    list($consumer, $callback) = $this->subscribers[$q->getConsumerTag()];

                    return call_user_func($callback, $message, $consumer);
                } finally {
                    $extConnection->setReadTimeout($consumeTimeout);
                }
            }, AMQP_JUST_CONSUME);
        } catch (\AMQPQueueException $e) {
            if ('Consumer timeout exceed' == $e->getMessage()) {
                return;
            }

            throw $e;
        } finally {
            $extConnection->setReadTimeout($originalTimeout);
        }
    }

    /**
     * @param AmqpConsumer $consumer
     */
    public function subscribe(PsrConsumer $consumer, callable $callback): void
    {
        if (false == $consumer instanceof AmqpConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', AmqpConsumer::class, get_class($consumer)));
        }

        if ($consumer->getConsumerTag() && array_key_exists($consumer->getConsumerTag(), $this->subscribers)) {
            return;
        }

        $extQueue = new \AMQPQueue($this->context->getExtChannel());
        $extQueue->setName($consumer->getQueue()->getQueueName());

        $extQueue->consume(null, Flags::convertConsumerFlags($consumer->getFlags()), $consumer->getConsumerTag());

        $consumerTag = $extQueue->getConsumerTag();
        $consumer->setConsumerTag($consumerTag);
        $this->subscribers[$consumerTag] = [$consumer, $callback, $extQueue];
    }

    /**
     * @param AmqpConsumer $consumer
     */
    public function unsubscribe(PsrConsumer $consumer): void
    {
        if (false == $consumer instanceof AmqpConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', AmqpConsumer::class, get_class($consumer)));
        }

        if (false == $consumer->getConsumerTag()) {
            return;
        }

        $consumerTag = $consumer->getConsumerTag();
        $consumer->setConsumerTag(null);

        list($consumer, $callback, $extQueue) = $this->subscribers[$consumerTag];

        $extQueue->cancel($consumerTag);
        unset($this->subscribers[$consumerTag]);
    }

    public function unsubscribeAll(): void
    {
        foreach ($this->subscribers as list($consumer)) {
            $this->unsubscribe($consumer);
        }
    }
}
