<?php

namespace Enqueue\Client;

interface MessageProducerInterface
{
    /**
     * Sends a message to a topic. There are some message processor may be subscribed to a topic.
     *
     * @param string               $topic
     * @param string|array|Message $message
     *
     * @throws \Enqueue\Psr\Exception - if the producer fails to send
     *                                the message due to some internal error
     */
    public function send($topic, $message);
}
