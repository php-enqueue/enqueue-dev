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
     *
     * @return Promise|null the promise is returned if message has reply to set
     */
    public function sendCommand($command, $message);
}
