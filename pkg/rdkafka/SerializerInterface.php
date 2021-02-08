<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

interface SerializerInterface
{
    public function toString(RdKafkaMessageInterface $message): string;

    public function toMessage(string $string): RdKafkaMessageInterface;
}
