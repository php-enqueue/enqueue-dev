<?php

namespace Enqueue\Tests\Consumption\Mock;

use Enqueue\Consumption\Context;
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

    public function onIdle(Context $context)
    {
        if ($this->cycles >= $this->limit) {
            $context->setExecutionInterrupted(true);
        } else {
            ++$this->cycles;
        }
    }
}
