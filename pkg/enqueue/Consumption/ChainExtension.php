<?php

namespace Enqueue\Consumption;

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
        $this->extensions = $extensions;
    }

    /**
     * @param Context $context
     */
    public function onStart(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onStart($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onBeforeReceive(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onBeforeReceive($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onPreReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreReceived($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onResult(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onResult($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostReceived($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onIdle(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onIdle($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onInterrupted(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onInterrupted($context);
        }
    }
}
