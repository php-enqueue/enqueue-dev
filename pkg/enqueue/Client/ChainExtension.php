<?php

namespace Enqueue\Client;

final class ChainExtension implements ExtensionInterface
{
    /**
     * @var PreSendEventExtensionInterface[]
     */
    private $preSendEventExtensions;

    /**
     * @var PreSendCommandExtensionInterface[]
     */
    private $preSendCommandExtensions;

    /**
     * @var DriverPreSendExtensionInterface[]
     */
    private $driverPreSendExtensions;

    /**
     * @var PostSendExtensionInterface[]
     */
    private $postSendExtensions;

    public function __construct(array $extensions)
    {
        $this->preSendEventExtensions = [];
        $this->preSendCommandExtensions = [];
        $this->driverPreSendExtensions = [];
        $this->postSendExtensions = [];

        array_walk($extensions, function ($extension) {
            if ($extension instanceof ExtensionInterface) {
                $this->preSendEventExtensions[] = $extension;
                $this->preSendCommandExtensions[] = $extension;
                $this->driverPreSendExtensions[] = $extension;
                $this->postSendExtensions[] = $extension;

                return;
            }

            $extensionValid = false;
            if ($extension instanceof PreSendEventExtensionInterface) {
                $this->preSendEventExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof PreSendCommandExtensionInterface) {
                $this->preSendCommandExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof DriverPreSendExtensionInterface) {
                $this->driverPreSendExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof PostSendExtensionInterface) {
                $this->postSendExtensions[] = $extension;

                $extensionValid = true;
            }

            if (false == $extensionValid) {
                throw new \LogicException(sprintf('Invalid extension given %s', get_class($extension)));
            }
        });
    }

    public function onPreSendEvent(PreSend $context): void
    {
        foreach ($this->preSendEventExtensions as $extension) {
            $extension->onPreSendEvent($context);
        }
    }

    public function onPreSendCommand(PreSend $context): void
    {
        foreach ($this->preSendCommandExtensions as $extension) {
            $extension->onPreSendCommand($context);
        }
    }

    public function onDriverPreSend(DriverPreSend $context): void
    {
        foreach ($this->driverPreSendExtensions as $extension) {
            $extension->onDriverPreSend($context);
        }
    }

    public function onPostSend(PostSend $context): void
    {
        foreach ($this->postSendExtensions as $extension) {
            $extension->onPostSend($context);
        }
    }
}
