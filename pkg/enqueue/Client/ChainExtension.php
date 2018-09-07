<?php

namespace Enqueue\Client;

final class ChainExtension implements ExtensionInterface
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
        array_walk($extensions, function (ExtensionInterface $extension) {
            $this->extensions[] = $extension;
        });
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

    public function onDriverPreSend(DriverPreSend $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onDriverPreSend($context);
        }
    }

    public function onPostSend(PostSend $event): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostSend($event);
        }
    }
}
