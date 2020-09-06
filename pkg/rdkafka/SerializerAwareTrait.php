<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

trait SerializerAwareTrait
{
    /**
     * @var Serializer|null
     */
    private $serializer;

    /**
     * @param Serializer|null $serializer
     */
    public function setSerializer(?Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return Serializer|null
     */
    public function getSerializer()
    {
        return $this->serializer;
    }
}
