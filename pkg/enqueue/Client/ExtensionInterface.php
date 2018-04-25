<?php

namespace Enqueue\Client;

interface ExtensionInterface
{
    /**
     * @param string  $topic
     * @param Message $message
     *
     * @return
     */
    public function onPreSend($topic, Message $message);

    /**
     * @param string  $topic
     * @param Message $message
     *
     * @return
     */
    public function onPostSend($topic, Message $message);
}
