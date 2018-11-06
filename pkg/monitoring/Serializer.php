<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

interface Serializer
{
    public function toString(Stats $stats): string;
}
