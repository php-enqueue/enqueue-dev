<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

interface StatsStorageFactory
{
    public function create(string $dsn): StatsStorage;
}
