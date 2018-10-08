<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
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

    public function onPreSubscribe(PreSubscribe $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreSubscribe($context);
        }
    }

    public function onPreConsume(PreConsume $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreConsume($context);
        }
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onMessageReceived($context);
        }
    }

    public function onResult(MessageResult $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onResult($context);
        }
    }

    public function onProcessorException(ProcessorException $context): void
    {
        foreach ($this->extensions as $extension) {
            $extension->onProcessorException($context);
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
