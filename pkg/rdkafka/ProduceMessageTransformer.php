<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

interface ProduceMessageTransformer
{
    public function transformProduceMessage(RdKafkaMessage $message);
}
