<?php

namespace Enqueue\AmqpExt;

use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;

class Flags
{
    /**
     * @param int $interop
     *
     * @return int
     */
    public static function convertMessageFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        if ($interop & AmqpMessage::FLAG_MANDATORY) {
            $flags |= AMQP_MANDATORY;
        }

        if ($interop & AmqpMessage::FLAG_IMMEDIATE) {
            $flags |= AMQP_IMMEDIATE;
        }

        return $flags;
    }

    /**
     * @param int $interop
     *
     * @return int
     */
    public static function convertTopicFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        $flags |= static::convertDestinationFlags($interop);

        if ($interop & AmqpTopic::FLAG_INTERNAL) {
            $flags |= AMQP_INTERNAL;
        }

        return $flags;
    }

    /**
     * @param int $interop
     *
     * @return int
     */
    public static function convertQueueFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        $flags |= static::convertDestinationFlags($interop);

        if ($interop & AmqpQueue::FLAG_EXCLUSIVE) {
            $flags |= AMQP_EXCLUSIVE;
        }

        return $flags;
    }

    /**
     * @param int $interop
     *
     * @return int
     */
    public static function convertDestinationFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        if ($interop & AmqpDestination::FLAG_PASSIVE) {
            $flags |= AMQP_PASSIVE;
        }

        if ($interop & AmqpDestination::FLAG_DURABLE) {
            $flags |= AMQP_DURABLE;
        }

        if ($interop & AmqpDestination::FLAG_AUTODELETE) {
            $flags |= AMQP_AUTODELETE;
        }

        if ($interop & AmqpDestination::FLAG_NOWAIT) {
            $flags |= AMQP_NOWAIT;
        }

        return $flags;
    }

    /**
     * @param int $interop
     *
     * @return int
     */
    public static function convertConsumerFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        if ($interop & AmqpConsumer::FLAG_NOLOCAL) {
            $flags |= AMQP_NOLOCAL;
        }

        if ($interop & AmqpConsumer::FLAG_NOACK) {
            $flags |= AMQP_AUTOACK;
        }

        if ($interop & AmqpConsumer::FLAG_EXCLUSIVE) {
            $flags |= AMQP_EXCLUSIVE;
        }

        if ($interop & AmqpConsumer::FLAG_NOWAIT) {
            $flags |= AMQP_NOWAIT;
        }

        return $flags;
    }
}
