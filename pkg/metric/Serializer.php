<?php

declare(strict_types=1);

namespace Enqueue\Metric;

interface Serializer
{
    public function toString(Event $event): string;
}
