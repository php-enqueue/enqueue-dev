<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\Start;

class ChainExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var ExtensionInterface[]
     */
    private $extensions;

    /**
     * @param ExtensionInterface[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = [];
        array_walk($extensions, function (ExtensionInterface $extension) {
            $this->extensions[] = $extension;
        });
    }

    public function onStart(Start $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onStart($context);
        }
    }

    public function preSubscribe(PreSubscribe $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->preSubscribe($context);
        }
    }

    public function onBeforeReceive(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onBeforeReceive($context);
        }
    }

    public function onPreReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreReceived($context);
        }
    }

    public function onResult(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onResult($context);
        }
    }

    public function onPostReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostReceived($context);
        }
    }

    public function onIdle(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onIdle($context);
        }
    }

    public function onInterrupted(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onInterrupted($context);
        }
    }
}
