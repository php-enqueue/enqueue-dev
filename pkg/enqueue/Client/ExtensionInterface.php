<?php

namespace Enqueue\Client;

interface ExtensionInterface extends PreSendEventExtensionInterface, PreSendCommandExtensionInterface, DriverPreSendExtensionInterface, PostSendExtensionInterface
{
}
