<?php

declare(strict_types=1);

namespace Enqueue\AmqpTools;

interface DelayStrategyAware
{
    public function setDelayStrategy(DelayStrategy $delayStrategy = null): self;
}
