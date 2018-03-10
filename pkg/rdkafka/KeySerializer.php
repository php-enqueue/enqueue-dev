<?php

namespace Enqueue\RdKafka;

interface KeySerializer
{
    /**
     * @param mixed $key
     *
     * @return string
     */
    public function toString($key);

    /**
     * @param string $serialized
     *
     * @return mixed
     */
    public function toKey($serialized);
}
