<?php

namespace Enqueue\Client;

use Enqueue\Rpc\Promise;

interface ProducerV2Interface
{
    /**
     * @experimental
     *
     * @param string               $topic
     * @param string|array|Message $message
     */
    public function sendEvent($topic, $message);

    /**
     * @experimental
     *
     * @param string               $command
     * @param string|array|Message $message
     *
     * @return Promise|null the promise is returned if message has reply to set
     */
    public function sendCommand($command, $message);
}
