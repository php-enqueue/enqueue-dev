<?php

namespace Enqueue\Psr;

/**
 * A client uses a MessageConsumer object to receive messages from a destination.
 * A MessageConsumer object is created by passing a Destination object
 * to a message-consumer creation method supplied by a session.
 *
 * @see https://docs.oracle.com/javaee/7/api/javax/jms/MessageConsumer.html
 */
interface PsrConsumer
{
    /**
     * Gets the Queue associated with this queue receiver.
     *
     * @return PsrQueue
     */
    public function getQueue();

    /**
     * Receives the next message that arrives within the specified timeout interval.
     * This call blocks until a message arrives, the timeout expires, or this message consumer is closed.
     * A timeout of zero never expires, and the call blocks indefinitely.
     *
     * @param int $timeout the timeout value (in milliseconds)
     *
     * @return PsrMessage|null
     */
    public function receive($timeout = 0);

    /**
     * Receives the next message if one is immediately available.
     *
     * @return PsrMessage|null
     */
    public function receiveNoWait();

    /**
     * Tell the MQ broker that the message was processed successfully.
     *
     * @param PsrMessage $message
     */
    public function acknowledge(PsrMessage $message);

    /**
     * Tell the MQ broker that the message was rejected.
     *
     * @param PsrMessage $message
     * @param bool       $requeue
     */
    public function reject(PsrMessage $message, $requeue = false);
}
