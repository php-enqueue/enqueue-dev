<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

interface StatsStorage
{
    public function pushConsumerStats(ConsumerStats $event): void;

    public function pushMessageStats(MessageStats $event): void;
}
