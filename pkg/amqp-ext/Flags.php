<?php

namespace Enqueue\AmqpExt;

use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Amqp\AmqpDestination as InteropAmqpDestination;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Amqp\AmqpTopic as InteropAmqpTopic;

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

        if ($interop & InteropAmqpMessage::FLAG_MANDATORY) {
            $flags |= AMQP_MANDATORY;
        }

        if ($interop & InteropAmqpMessage::FLAG_IMMEDIATE) {
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

        if ($interop & InteropAmqpTopic::FLAG_INTERNAL) {
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

        if ($interop & InteropAmqpQueue::FLAG_EXCLUSIVE) {
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

        if ($interop & InteropAmqpDestination::FLAG_PASSIVE) {
            $flags |= AMQP_PASSIVE;
        }

        if ($interop & InteropAmqpDestination::FLAG_DURABLE) {
            $flags |= AMQP_DURABLE;
        }

        if ($interop & InteropAmqpDestination::FLAG_AUTODELETE) {
            $flags |= AMQP_AUTODELETE;
        }

        if ($interop & InteropAmqpDestination::FLAG_NOWAIT) {
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

        if ($interop & InteropAmqpConsumer::FLAG_NOLOCAL) {
            $flags |= AMQP_NOLOCAL;
        }

        if ($interop & InteropAmqpConsumer::FLAG_NOACK) {
            $flags |= AMQP_AUTOACK;
        }

        if ($interop & InteropAmqpConsumer::FLAG_EXCLUSIVE) {
            $flags |= AMQP_EXCLUSIVE;
        }

        if ($interop & InteropAmqpConsumer::FLAG_NOWAIT) {
            $flags |= AMQP_NOWAIT;
        }

        return $flags;
    }
}
