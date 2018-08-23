<?php

namespace Enqueue\RdKafka;

interface Serializer
{
    public function toString(RdKafkaMessage $message): string;

    public function toMessage(string $string): RdKafkaMessage;
}
