<?php

namespace Enqueue\AmqpTools;

trait DelayStrategyAwareTrait
{
    /**
     * @var DelayStrategy
     */
    protected $delayStrategy;

    /**
     * {@inheritdoc}
     */
    public function setDelayStrategy(DelayStrategy $delayStrategy = null)
    {
        $this->delayStrategy = $delayStrategy;
    }
}
