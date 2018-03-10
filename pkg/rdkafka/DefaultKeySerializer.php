<?php

namespace Enqueue\RdKafka;

class DefaultKeySerializer implements KeySerializer
{
    /**
     * {@inheritdoc}
     */
    public function toString($key)
    {
        return (string) $key;
    }

    /**
     * {@inheritdoc}
     */
    public function toKey($serialized)
    {
        return $serialized;
    }
}
