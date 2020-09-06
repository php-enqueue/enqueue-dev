<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

final class NullMessageTransformer implements ConsumeMessageTransformer, ProduceMessageTransformer
{

    public function transformConsumeMessage(RdKafkaMessage $message)
    {
        return;
    }

    public function transformProduceMessage(RdKafkaMessage $message)
    {
        return;
    }
}
