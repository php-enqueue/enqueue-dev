<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

trait SerializerAwareTrait
{
    /**
     * @var SerializerInterface|null
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return SerializerInterface|null
     */
    public function getSerializer(): ?SerializerInterface
    {
        return $this->serializer;
    }
}
