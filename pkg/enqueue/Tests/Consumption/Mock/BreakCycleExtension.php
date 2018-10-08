<?php

namespace Enqueue\Tests\Consumption\Mock;

use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class BreakCycleExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

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

    public function onPostConsume(PostConsume $context): void
    {
        if ($this->cycles >= $this->limit) {
            $context->interruptExecution();
        } else {
            ++$this->cycles;
        }
    }
}
