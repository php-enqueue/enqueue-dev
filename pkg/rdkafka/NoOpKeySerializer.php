<?php

namespace Enqueue\RdKafka;

class NoOpKeySerializer implements KeySerializer
{
    /**
     * {@inheritdoc}
     */
    public function toString($key)
    {
        return $key;
    }

    /**
     * {@inheritdoc}
     */
    public function toKey($serialized)
    {
        return $serialized;
    }
}
