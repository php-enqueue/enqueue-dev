<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Interop\Queue\ConnectionFactory;

interface RdKafkaConnectionFactoryInterface extends ConnectionFactory
{
    public function getConfig(): array;
}
