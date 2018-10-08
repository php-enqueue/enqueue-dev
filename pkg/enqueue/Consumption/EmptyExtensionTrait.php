<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;

trait EmptyExtensionTrait
{
    public function onStart(Start $context): void
    {
    }

    public function onPreSubscribe(PreSubscribe $preSubscribe): void
    {
    }

    public function onPreConsume(PreConsume $context): void
    {
    }

    public function onMessageReceived(MessageReceived $context): void
    {
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
    }

    public function onResult(MessageResult $context): void
    {
    }

    public function onProcessorException(ProcessorException $context): void
    {
    }

    public function onPostConsume(PostConsume $context): void
    {
    }

    public function onInterrupted(Context $context)
    {
    }
}
