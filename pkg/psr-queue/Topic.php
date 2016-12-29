<?php
namespace Enqueue\Psr;

/**
 * A Topic object encapsulates a provider-specific topic name.
 * It is the way a client specifies the identity of a topic to transport methods.
 * For those methods that use a Destination as a parameter, a Topic object may used as an argument.
 *
 * @link https://docs.oracle.com/javaee/7/api/javax/jms/Topic.html
 */
interface Topic extends Destination
{
    /**
     * Gets the name of this topic. This is a destination one sends messages to.
     *
     * @return string
     */
    public function getTopicName();
}
