<?php

namespace Enqueue;

use Interop\Queue\ConnectionFactory;

interface ConnectionFactoryFactoryInterface
{
    /**
     * If string is used, it should be a valid DSN.
     *
     * If array is used, it must have a dsn key with valid DSN string.
     * The other array options are treated as default values.
     * Options from DSN overwrite them.
     *
     *
     * @param string|array $config
     *
     * @throws \InvalidArgumentException if invalid config provided
     */
    public function create($config): ConnectionFactory;
}
