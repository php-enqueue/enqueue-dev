<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
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

    public function onResult(MessageResult $context): void
    {
    }

    public function onPostReceived(Context $context)
    {
    }

    public function onIdle(Context $context)
    {
    }

    public function onInterrupted(Context $context)
    {
    }
}
