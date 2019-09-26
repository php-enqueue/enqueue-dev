<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use RdKafka\Message as VendorMessage;

interface Serializer
{
    public function toString(RdKafkaMessage $message): string;

    public function toMessage(VendorMessage $string): RdKafkaMessage;
}
