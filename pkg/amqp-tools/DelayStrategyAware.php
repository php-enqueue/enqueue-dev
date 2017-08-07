<?php

namespace Enqueue\AmqpTools;

interface DelayStrategyAware
{
    /**
     * @param DelayStrategy $delayStrategy
     *
     * @return self
     */
    public function setDelayStrategy(DelayStrategy $delayStrategy = null);
}
