<?php

namespace Enqueue\RdKafka;

class JsonKeySerializer implements KeySerializer
{
    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function toString($key)
    {
        return json_encode($key, JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function toKey($serialized)
    {
        return json_decode($serialized);
    }
}
