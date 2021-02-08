<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

interface SerializerAwareInterface
{
    public function setSerializer(SerializerInterface $serializer);

    public function getSerializer(): ?SerializerInterface;
}
