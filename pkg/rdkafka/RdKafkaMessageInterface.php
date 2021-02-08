<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Interop\Queue\Message;
use RdKafka\Message as VendorMessage;

interface RdKafkaMessageInterface extends Message
{
    public function getPartition(): ?int;

    public function setPartition(int $partition = null): void;

    public function getKey(): ?string;

    public function setKey(string $key = null): void;

    public function getKafkaMessage(): ?VendorMessage;

    public function setKafkaMessage(VendorMessage $message = null): void;
}
