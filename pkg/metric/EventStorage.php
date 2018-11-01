<?php

declare(strict_types=1);

namespace Enqueue\Metric;

interface EventStorage
{
    public function onConsumerStarted(ConsumerStarted $event);

    public function onConsumerStopped(ConsumerStopped $event);

    public function onConsumerStats(ConsumerStats $event);

    public function onMessageStats(MessageStats $event);
}
