<?php

namespace Enqueue\Client;

use Enqueue\Rpc\Promise;

interface ProducerInterface
{
    /**
     * The message could be pretty much everything as long as you have a client extension that transforms a body to string on onPreSendEvent.
     *
     * @param string|array|Message $message
     *
     * @throws \Exception
     */
    public function sendEvent(string $topic, $message): void;

    /**
     * The message could be pretty much everything as long as you have a client extension that transforms a body to string on onPreSendCommand.
     * The promise is returned if needReply argument is true.
     *
     * @param string|array|Message $message
     *
     * @throws \Exception
     */
    public function sendCommand(string $command, $message, bool $needReply = false): ?Promise;
}
