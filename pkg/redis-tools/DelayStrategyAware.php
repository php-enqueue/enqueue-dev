<?php

namespace Enqueue\RedisTools;

interface DelayStrategyAware
{
    /**
     * @param DelayStrategy $delayStrategy
     *
     * @return self
     */
    public function setDelayStrategy(DelayStrategy $delayStrategy = null);
}
