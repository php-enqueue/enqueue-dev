<?php

namespace Enqueue\AmqpLib;

use Enqueue\AmqpTools\SignalSocketHelper;
use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Queue\Exception;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrSubscriptionConsumer;
use PhpAmqpLib\Exception\AMQPIOWaitException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;

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

        $this->context->getLibChannel()->basic_cancel($consumerTag);

        $consumer->setConsumerTag(null);
        unset($this->subscribers[$consumerTag], $this->context->getLibChannel()->callbacks[$consumerTag]);
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
