<?php

namespace Enqueue\AmqpTools;

trait DelayStrategyAwareTrait
{
    /**
     * @var DelayStrategy
     */
    protected $delayStrategy;

    /**
     * @param DelayStrategy|null $delayStrategy
     *
     * @return self
     */
    public function setDelayStrategy(DelayStrategy $delayStrategy = null)
    {
        $this->delayStrategy = $delayStrategy;

        return $this;
    }
}
