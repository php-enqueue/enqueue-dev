<?php

namespace Enqueue\RdKafka;

trait KeySerializerAwareTrait
{
    /**
     * @var KeySerializer
     */
    private $keySerializer;

    /**
     * @param KeySerializer $keySerializer
     */
    public function setKeySerializer(KeySerializer $keySerializer)
    {
        $this->keySerializer = $keySerializer;
    }

    /**
     * @return KeySerializer
     */
    public function getKeySerializer()
    {
        return $this->keySerializer;
    }
}
