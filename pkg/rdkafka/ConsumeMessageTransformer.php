<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

interface ConsumeMessageTransformer
{
    public function transformConsumeMessage(RdKafkaMessage $message);
}
