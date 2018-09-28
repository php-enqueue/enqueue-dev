<?php

namespace Enqueue\Rpc;

use Interop\Queue\Message as InteropMessage;

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
     * @var InteropMessage
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
     * @param int $timeout
     *
     * @throws TimeoutException if the wait timeout is reached
     *
     * @return InteropMessage
     */
    public function receive($timeout = null)
    {
        if (null == $this->message) {
            try {
                if ($message = $this->doReceive($this->receiveCallback, $this, $timeout)) {
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
     * @return InteropMessage|null
     */
    public function receiveNoWait()
    {
        if (null == $this->message) {
            if ($message = $this->doReceive($this->receiveNoWaitCallback, $this)) {
                $this->message = $message;

                call_user_func($this->finallyCallback, $this);
            }
        }

        return $this->message;
    }

    /**
     * On TRUE deletes reply queue after getMessage call.
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

    /**
     * @param \Closure $cb
     * @param array    $args
     *
     * @return InteropMessage
     */
    private function doReceive(\Closure $cb, ...$args)
    {
        $message = call_user_func_array($cb, $args);

        if (null !== $message && false == $message instanceof InteropMessage) {
            throw new \RuntimeException(sprintf(
                'Expected "%s" but got: "%s"', InteropMessage::class, is_object($message) ? get_class($message) : gettype($message)));
        }

        return $message;
    }
}
