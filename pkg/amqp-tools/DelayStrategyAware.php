<?php

namespace Enqueue\AmqpTools;

interface DelayStrategyAware
{
    /**
     * @param DelayStrategy $delayStrategy
     */
    public function setDelayStrategy(DelayStrategy $delayStrategy = null);
}
