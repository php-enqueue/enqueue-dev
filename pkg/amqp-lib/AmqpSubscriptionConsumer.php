<?php

declare(strict_types=1);

namespace Enqueue\AmqpLib;

use Enqueue\AmqpTools\SignalSocketHelper;
use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Amqp\AmqpSubscriptionConsumer as InteropAmqpSubscriptionConsumer;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\Exception;
use PhpAmqpLib\Exception\AMQPIOWaitException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;

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

    /**
     * @var bool
     */
    private $heartbeatOnTick;

    public function __construct(AmqpContext $context, bool $heartbeatOnTick)
    {
        $this->context = $context;
        $this->heartbeatOnTick = $heartbeatOnTick;
    }

    public function consume(int $timeout = 0): void
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('There is no subscribers. Consider calling basicConsumeSubscribe before consuming');
        }

        $signalHandler = new SignalSocketHelper();
        $signalHandler->beforeSocket();

        $heartbeatOnTick = function (AmqpContext $context) {
            $context->getLibChannel()->getConnection()->getIO()->check_heartbeat();
        };

        $this->heartbeatOnTick && register_tick_function($heartbeatOnTick, $this->context);

        try {
            while (true) {
                $start = microtime(true);

                $this->context->getLibChannel()->wait(null, false, $timeout / 1000);

                if ($timeout <= 0) {
                    continue;
                }

                // compute remaining timeout and continue until time is up
                $stop = microtime(true);
                $timeout -= ($stop - $start) * 1000;

                if ($timeout <= 0) {
                    break;
                }
            }
        } catch (AMQPTimeoutException $e) {
        } catch (StopBasicConsumptionException $e) {
        } catch (AMQPIOWaitException $e) {
            if ($signalHandler->wasThereSignal()) {
                return;
            }

            throw $e;
        } finally {
            $signalHandler->afterSocket();

            $this->heartbeatOnTick && unregister_tick_function($heartbeatOnTick);
        }
    }

    /**
     * @param AmqpConsumer $consumer
     */
    public function subscribe(Consumer $consumer, callable $callback): void
    {
        if (false == $consumer instanceof AmqpConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', AmqpConsumer::class, get_class($consumer)));
        }

        if ($consumer->getConsumerTag() && array_key_exists($consumer->getConsumerTag(), $this->subscribers)) {
            return;
        }

        $libCallback = function (LibAMQPMessage $message) {
            $receivedMessage = $this->context->convertMessage($message);
            $receivedMessage->setConsumerTag($message->delivery_info['consumer_tag']);

            /**
             * @var AmqpConsumer
             * @var callable     $callback
             */
            list($consumer, $callback) = $this->subscribers[$message->delivery_info['consumer_tag']];

            if (false === call_user_func($callback, $receivedMessage, $consumer)) {
                throw new StopBasicConsumptionException();
            }
        };

        $consumerTag = $this->context->getLibChannel()->basic_consume(
            $consumer->getQueue()->getQueueName(),
            $consumer->getConsumerTag(),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOLOCAL),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOACK),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_EXCLUSIVE),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOWAIT),
            $libCallback
        );

        if (empty($consumerTag)) {
            throw new Exception('Got empty consumer tag');
        }

        $consumer->setConsumerTag($consumerTag);

        $this->subscribers[$consumerTag] = [$consumer, $callback];
    }

    /**
     * @param AmqpConsumer $consumer
     */
    public function unsubscribe(Consumer $consumer): void
    {
        if (false == $consumer instanceof AmqpConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', AmqpConsumer::class, get_class($consumer)));
        }

        if (false == $consumer->getConsumerTag()) {
            return;
        }

        $consumerTag = $consumer->getConsumerTag();

        $this->context->getLibChannel()->basic_cancel($consumerTag);

        $consumer->setConsumerTag(null);
        unset($this->subscribers[$consumerTag], $this->context->getLibChannel()->callbacks[$consumerTag]);
    }

    public function unsubscribeAll(): void
    {
        foreach ($this->subscribers as list($consumer)) {
            $this->unsubscribe($consumer);
        }
    }
}
