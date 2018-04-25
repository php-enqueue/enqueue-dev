<?php

namespace Enqueue\Rpc;

class TimeoutException extends \LogicException
{
    /**
     * @param int    $timeout
     * @param string $correlationId
     *
     * @return static
     */
    public static function create($timeout, $correlationId)
    {
        return new static(sprintf('Rpc call timeout is reached without receiving a reply message. Timeout: %s, CorrelationId: %s', $timeout, $correlationId));
    }
}
