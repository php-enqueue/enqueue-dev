<?php

namespace Enqueue\Tests\Consumption\Mock;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\ExtensionInterface;

class BreakCycleExtension implements ExtensionInterface
{
    protected $cycles = 1;

    private $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        if ($this->cycles >= $this->limit) {
            $context->interruptExecution();
        } else {
            ++$this->cycles;
        }
    }

    public function onEnd(End $context): void
    {
    }

    public function onMessageReceived(MessageReceived $context): void
    {
    }

    public function onResult(MessageResult $context): void
    {
    }

    public function onPreConsume(PreConsume $context): void
    {
    }

    public function onPreSubscribe(PreSubscribe $context): void
    {
    }

    public function onProcessorException(ProcessorException $context): void
    {
    }

    public function onStart(Start $context): void
    {
    }

    public function onPostConsume(PostConsume $context): void
    {
        if ($this->cycles >= $this->limit) {
            $context->interruptExecution();
        } else {
            ++$this->cycles;
        }
    }
}
