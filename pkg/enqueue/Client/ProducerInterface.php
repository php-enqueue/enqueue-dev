<?php

namespace Enqueue\Client;

use Enqueue\Rpc\Promise;

interface ProducerInterface
{
    /**
     * @param string               $topic
     * @param string|array|Message $message
     */
    public function sendEvent($topic, $message);

    /**
     * @param string               $command
     * @param string|array|Message $message
     * @param bool                 $needReply
     *
     * @return Promise|null the promise is returned if needReply argument is true
     */
    public function sendCommand($command, $message, $needReply = false);

    /**
     * @deprecated use sendEvent method.
     *
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
