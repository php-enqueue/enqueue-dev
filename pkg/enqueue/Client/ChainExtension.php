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

    /**
     * @param OnPrepareMessage $context
     */
    public function onPrepareMessage(OnPrepareMessage $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPrepareMessage($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onSend(OnSend $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onSend($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSend(OnPostSend $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostSend($context);
        }
    }
}
