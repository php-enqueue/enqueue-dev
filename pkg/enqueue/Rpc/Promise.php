<?php

namespace Enqueue\Rpc;

use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrMessage;

class Promise
{
    /**
     * @var PsrConsumer
     */
    private $consumer;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string
     */
    private $correlationId;

    /**
     * @param PsrConsumer $consumer
     * @param string   $correlationId
     * @param int      $timeout
     */
    public function __construct(PsrConsumer $consumer, $correlationId, $timeout)
    {
        $this->consumer = $consumer;
        $this->timeout = $timeout;
        $this->correlationId = $correlationId;
    }

    /**
     * @throws TimeoutException if the wait timeout is reached
     *
     * @return PsrMessage
     */
    public function getMessage()
    {
        $endTime = time() + $this->timeout;

        while (time() < $endTime) {
            if ($message = $this->consumer->receive($this->timeout)) {
                if ($message->getCorrelationId() === $this->correlationId) {
                    $this->consumer->acknowledge($message);

                    return $message;
                }
                $this->consumer->reject($message, true);
            }
        }

        throw TimeoutException::create($this->timeout, $this->correlationId);
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
