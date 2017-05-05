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
     * {@inheritdoc}
     */
    public function onPreSend($topic, Message $message)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreSend($topic, $message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSend($topic, Message $message)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostSend($topic, $message);
        }
    }
}
