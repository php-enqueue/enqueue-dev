<?php

namespace Enqueue\Client;

interface ExtensionInterface
{
    public function onPreSendEvent(PreSend $context): void;

    public function onPreSendCommand(PreSend $context): void;

    public function onDriverPreSend(DriverPreSend $context): void;

    public function onPostSend(PostSend $context): void;

//    /**
//     * @deprecated
//     */
//    public function onPreSend($topic, Message $message);
//
//    /**
//     * @deprecated
//     */
//    public function onPostSend($topic, Message $message);
}
