<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;

class ChainExtension implements ExtensionInterface
{
    private $startExtensions;
    private $initLoggerExtensions;
    private $preSubscribeExtensions;
    private $preConsumeExtensions;
    private $messageReceivedExtensions;
    private $messageResultExtensions;
    private $postMessageReceivedExtensions;
    private $processorExceptionExtensions;
    private $postConsumeExtensions;
    private $endExtensions;

    public function __construct(array $extensions)
    {
        $this->startExtensions = [];
        $this->initLoggerExtensions = [];
        $this->preSubscribeExtensions = [];
        $this->preConsumeExtensions = [];
        $this->messageReceivedExtensions = [];
        $this->messageResultExtensions = [];
        $this->postMessageReceivedExtensions = [];
        $this->processorExceptionExtensions = [];
        $this->postConsumeExtensions = [];
        $this->endExtensions = [];

        array_walk($extensions, function ($extension) {
            if ($extension instanceof ExtensionInterface) {
                $this->startExtensions[] = $extension;
                $this->initLoggerExtensions[] = $extension;
                $this->preSubscribeExtensions[] = $extension;
                $this->preConsumeExtensions[] = $extension;
                $this->messageReceivedExtensions[] = $extension;
                $this->messageResultExtensions[] = $extension;
                $this->postMessageReceivedExtensions[] = $extension;
                $this->processorExceptionExtensions[] = $extension;
                $this->postConsumeExtensions[] = $extension;
                $this->endExtensions[] = $extension;

                return;
            }

            $extensionValid = false;
            if ($extension instanceof StartExtensionInterface) {
                $this->startExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof InitLoggerExtensionInterface) {
                $this->initLoggerExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof PreSubscribeExtensionInterface) {
                $this->preSubscribeExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof PreConsumeExtensionInterface) {
                $this->preConsumeExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof MessageReceivedExtensionInterface) {
                $this->messageReceivedExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof MessageResultExtensionInterface) {
                $this->messageResultExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof ProcessorExceptionExtensionInterface) {
                $this->processorExceptionExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof PostMessageReceivedExtensionInterface) {
                $this->postMessageReceivedExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof PostConsumeExtensionInterface) {
                $this->postConsumeExtensions[] = $extension;

                $extensionValid = true;
            }

            if ($extension instanceof EndExtensionInterface) {
                $this->endExtensions[] = $extension;

                $extensionValid = true;
            }

            if (false == $extensionValid) {
                throw new \LogicException(sprintf('Invalid extension given %s', get_class($extension)));
            }
        });
    }

    public function onInitLogger(InitLogger $context): void
    {
        foreach ($this->initLoggerExtensions as $extension) {
            $extension->onInitLogger($context);
        }
    }

    public function onStart(Start $context): void
    {
        foreach ($this->startExtensions as $extension) {
            $extension->onStart($context);
        }
    }

    public function onPreSubscribe(PreSubscribe $context): void
    {
        foreach ($this->preSubscribeExtensions as $extension) {
            $extension->onPreSubscribe($context);
        }
    }

    public function onPreConsume(PreConsume $context): void
    {
        foreach ($this->preConsumeExtensions as $extension) {
            $extension->onPreConsume($context);
        }
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        foreach ($this->messageReceivedExtensions as $extension) {
            $extension->onMessageReceived($context);
        }
    }

    public function onResult(MessageResult $context): void
    {
        foreach ($this->messageResultExtensions as $extension) {
            $extension->onResult($context);
        }
    }

    public function onProcessorException(ProcessorException $context): void
    {
        foreach ($this->processorExceptionExtensions as $extension) {
            $extension->onProcessorException($context);
        }
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        foreach ($this->postMessageReceivedExtensions as $extension) {
            $extension->onPostMessageReceived($context);
        }
    }

    public function onPostConsume(PostConsume $context): void
    {
        foreach ($this->postConsumeExtensions as $extension) {
            $extension->onPostConsume($context);
        }
    }

    public function onEnd(End $context): void
    {
        foreach ($this->endExtensions as $extension) {
            $extension->onEnd($context);
        }
    }
}
