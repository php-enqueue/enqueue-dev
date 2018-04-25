<?php

namespace Enqueue\AmqpExt;

use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrMessage;

class AmqpConsumer implements InteropAmqpConsumer
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var AmqpQueue
     */
    private $queue;

    /**
     * @var Buffer
     */
    private $buffer;

    /**
     * @var \AMQPQueue
     */
    private $extQueue;

    /**
     * @var string
     */
    private $receiveMethod;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @param AmqpContext $context
     * @param AmqpQueue   $queue
     * @param Buffer      $buffer
     * @param string      $receiveMethod
     */
    public function __construct(AmqpContext $context, AmqpQueue $queue, Buffer $buffer, $receiveMethod)
    {
        $this->queue = $queue;
        $this->context = $context;
        $this->buffer = $buffer;
        $this->receiveMethod = $receiveMethod;
        $this->flags = self::FLAG_NOPARAM;
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * {@inheritdoc}
     */
    public function clearFlags()
    {
        $this->flags = self::FLAG_NOPARAM;
    }

    /**
     * {@inheritdoc}
     */
    public function addFlag($flag)
    {
        $this->flags |= $flag;
    }

    /**
     * {@inheritdoc}
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * {@inheritdoc}
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return InteropAmqpMessage|null
     */
    public function receive($timeout = 0)
    {
        if ('basic_get' == $this->receiveMethod) {
            return $this->receiveBasicGet($timeout);
        }

        if ('basic_consume' == $this->receiveMethod) {
            return $this->receiveBasicConsume($timeout);
        }

        throw new \LogicException('The "receiveMethod" is not supported');
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage|null
     */
    public function receiveNoWait()
    {
        if ($extMessage = $this->getExtQueue()->get(Flags::convertConsumerFlags($this->flags))) {
            return $this->context->convertMessage($extMessage);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $this->getExtQueue()->ack($message->getDeliveryTag());
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $this->getExtQueue()->reject(
            $message->getDeliveryTag(),
            $requeue ? AMQP_REQUEUE : AMQP_NOPARAM
        );
    }

    /**
     * @param int $timeout
     *
     * @return InteropAmqpMessage|null
     */
    private function receiveBasicGet($timeout)
    {
        $end = microtime(true) + ($timeout / 1000);

        while (0 === $timeout || microtime(true) < $end) {
            if ($message = $this->receiveNoWait()) {
                return $message;
            }

            usleep(100000); //100ms
        }
    }

    /**
     * @param int $timeout
     *
     * @return InteropAmqpMessage|null
     */
    private function receiveBasicConsume($timeout)
    {
        if (false == $this->consumerTag) {
            $this->context->subscribe($this, function (InteropAmqpMessage $message) {
                $this->buffer->push($message->getConsumerTag(), $message);

                return false;
            });
        }

        if ($message = $this->buffer->pop($this->consumerTag)) {
            return $message;
        }

        while (true) {
            $start = microtime(true);

            $this->context->consume($timeout);

            if ($message = $this->buffer->pop($this->consumerTag)) {
                return $message;
            }

            // is here when consumed message is not for this consumer

            // as timeout is infinite have to continue consumption, but it can overflow message buffer
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
    }

    /**
     * @return \AMQPQueue
     */
    private function getExtQueue()
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
