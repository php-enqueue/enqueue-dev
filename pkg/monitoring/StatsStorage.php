<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

interface StatsStorage
{
    public function pushConsumerStats(ConsumerStats $event);

    public function pushMessageStats(MessageStats $event);
}
