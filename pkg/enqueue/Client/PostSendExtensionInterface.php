<?php

namespace Enqueue\Client;

interface PostSendExtensionInterface
{
    public function onPostSend(PostSend $context): void;
}
