<?php

namespace Enqueue\Rpc;

use Enqueue\Psr\PsrMessage;

class Promise
{
    /**
     * @var \Closure
     */
    private $receiveCallback;

    /**
     * @var \Closure
     */
    private $finallyCallback;

    /**
     * @var bool
     */
    private $deleteReplyQueue;

    /**
     * @param \Closure $receiveCallback
     * @param \Closure $finallyCallback
     */
    public function __construct(\Closure $receiveCallback, \Closure $finallyCallback)
    {
        $this->receiveCallback = $receiveCallback;
        $this->finallyCallback = $finallyCallback;

        $this->deleteReplyQueue = true;
    }

    /**
     * @throws TimeoutException if the wait timeout is reached
     *
     * @return PsrMessage
     */
    public function getMessage()
    {
        try {
            $result = call_user_func($this->receiveCallback, $this);

            if (false == $result instanceof PsrMessage) {
                throw new \LogicException(sprintf(
                    'Expected "%s" but got: "%s"', PsrMessage::class, is_object($result) ? get_class($result) : gettype($result)));
            }

            return $result;
        } finally {
            call_user_func($this->finallyCallback, $this);
        }
    }

    /**
     * On TRUE deletes reply queue after getMessage call
     *
     * @param bool $delete
     */
    public function setDeleteReplyQueue($delete)
    {
        $this->deleteReplyQueue = (bool) $delete;
    }

    /**
     * @return bool
     */
    public function isDeleteReplyQueue()
    {
        return $this->deleteReplyQueue;
    }
}
