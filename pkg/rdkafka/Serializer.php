<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

/**
 * @deprecated Use ProduceMessageTransformer and/or ConsumeMessageTransformer instead
 */
interface Serializer
{
    public function toString(RdKafkaMessage $message): string;

    public function toMessage(string $string): RdKafkaMessage;
}
