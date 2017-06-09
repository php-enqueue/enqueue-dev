<?php

namespace Enqueue\Client;

use Enqueue\Rpc\Promise;

/**
 * @experimental
 */
interface ProducerV2Interface
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
}
