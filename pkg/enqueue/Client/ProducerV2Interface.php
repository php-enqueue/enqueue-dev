<?php

namespace Enqueue\Client;

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
     */
    public function sendCommand($command, $message);
}
