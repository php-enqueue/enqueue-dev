<?php

declare(strict_types=1);

namespace Enqueue\AmqpTools;

trait DelayStrategyAwareTrait
{
    /**
     * @var DelayStrategy
     */
    protected $delayStrategy;

    public function setDelayStrategy(DelayStrategy $delayStrategy = null): DelayStrategyAware
    {
        $this->delayStrategy = $delayStrategy;

        return $this;
    }
}
