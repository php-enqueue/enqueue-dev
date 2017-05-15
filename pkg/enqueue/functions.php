<?php

namespace Enqueue;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Psr\PsrConnectionFactory;
use Enqueue\Psr\PsrContext;

/**
 * @param string $dsn
 *
 * @return PsrConnectionFactory
 */
function dsn_to_connection_factory($dsn)
{
    $map = [];

    if (class_exists(FsConnectionFactory::class)) {
        $map['file'] = FsConnectionFactory::class;
    }

    if (class_exists(AmqpConnectionFactory::class)) {
        $map['amqp'] = AmqpConnectionFactory::class;
    }

    if (class_exists(NullConnectionFactory::class)) {
        $map['null'] = NullConnectionFactory::class;
    }

    list($scheme) = explode('://', $dsn);
    if (false == $scheme || false === strpos($dsn, '://')) {
        throw new \LogicException(sprintf('The scheme could not be parsed from DSN "%s"', $dsn));
    }

    if (false == array_key_exists($scheme, $map)) {
        throw new \LogicException(sprintf(
            'The scheme "%s" is not supported. Supported "%s"',
            $scheme,
            implode('", "', array_keys($map))
        ));
    }

    $factoryClass = $map[$scheme];

    return new $factoryClass($dsn);
}

/**
 * @param string $dsn
 *
 * @return PsrContext
 */
function dsn_to_context($dsn)
{
    return dsn_to_connection_factory($dsn)->createContext();
}
