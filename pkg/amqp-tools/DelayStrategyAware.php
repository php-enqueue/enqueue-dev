<?php

namespace Enqueue\AmqpTools;

interface DelayStrategyAware
{
    public function setDelayStrategy(DelayStrategy $delayStrategy = null): self;
}
