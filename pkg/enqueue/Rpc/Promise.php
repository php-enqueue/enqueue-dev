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
    private $receiveNoWaitCallback;

    /**
     * @var \Closure
     */
    private $finallyCallback;

    /**
     * @var bool
     */
    private $deleteReplyQueue;

    /**
     * @var PsrMessage
     */
    private $message;

    /**
     * @param \Closure $receiveCallback
     * @param \Closure $receiveNoWaitCallback
     * @param \Closure $finallyCallback
     */
    public function __construct(\Closure $receiveCallback, \Closure $receiveNoWaitCallback, \Closure $finallyCallback)
    {
        $this->receiveCallback = $receiveCallback;
        $this->receiveNoWaitCallback = $receiveNoWaitCallback;
        $this->finallyCallback = $finallyCallback;

        $this->deleteReplyQueue = true;
    }

    /**
     * Blocks until message received or timeout expired.
     *
     * @throws TimeoutException if the wait timeout is reached
     *
     * @return PsrMessage
     */
    public function receive()
    {
        if (null == $this->message) {
            try {
                if ($message = $this->doReceive($this->receiveCallback)) {
                    $this->message = $message;
                }
            } finally {
                call_user_func($this->finallyCallback, $this);
            }
        }

        return $this->message;
    }

    /**
     * Non blocking function. Returns message or null.
     *
     * @return PsrMessage|null
     */
    public function receiveNoWait()
    {
        if (null == $this->message) {
            if ($message = $this->doReceive($this->receiveNoWaitCallback)) {
                $this->message = $message;

                call_user_func($this->finallyCallback, $this);
            }
        }

        return $this->message;
    }

    /**
     * @param \Closure $cb
     *
     * @return PsrMessage
     */
    private function doReceive(\Closure $cb)
    {
        $message = call_user_func($cb, $this);

        if (null !== $message && false == $message instanceof PsrMessage) {
            throw new \RuntimeException(sprintf(
                'Expected "%s" but got: "%s"', PsrMessage::class, is_object($message) ? get_class($message) : gettype($message)));
        }

        return $message;
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
