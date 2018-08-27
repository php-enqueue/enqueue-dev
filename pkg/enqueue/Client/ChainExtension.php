<?php

namespace Enqueue\Client;

class ChainExtension implements ExtensionInterface
{
    /**
     * @var ExtensionInterface[]
     */
    private $extensions;

    /**
     * @param ExtensionInterface[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function onPreSendEvent(PreSend $event): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreSendEvent($event);
        }
    }

    public function onPreSendCommand(PreSend $event): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreSendCommand($event);
        }
    }

    public function onPreDriverSend(PreDriverSend $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreDriverSend($context);
        }
    }

    public function onPostSend(PostSend $event): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostSend($event);
        }
    }
}
