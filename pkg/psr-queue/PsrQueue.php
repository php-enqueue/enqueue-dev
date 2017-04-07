<?php

namespace Enqueue\Psr;

/**
 * A Queue object encapsulates a provider-specific queue name.
 * It is the way a client specifies the identity of a queue to transport methods.
 * For those methods that use a Destination as a parameter, a Queue object used as an argument.
 *
 * @see https://docs.oracle.com/javaee/7/api/javax/jms/Queue.html
 */
interface PsrQueue extends PsrDestination
{
    /**
     * Gets the name of this queue. This is a destination one consumes messages from.
     *
     * @return string
     */
    public function getQueueName();
}
