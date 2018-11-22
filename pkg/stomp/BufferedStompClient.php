<?php

namespace Enqueue\Stomp;

use Stomp\Client;
use Stomp\Transport\Frame;

class BufferedStompClient extends Client
{
    /**
     * [
     *   'subscriptionId' => Frame[],
     * ].
     *
     * @var array
     */
    private $buffer;

    /**
     * @var int int
     */
    private $bufferSize;

    /**
     * @var int
     */
    private $currentBufferSize;

    /**
     * @param \Stomp\Network\Connection|string $broker
     * @param int                              $bufferSize
     */
    public function __construct($broker, $bufferSize = 1000)
    {
        parent::__construct($broker);

        $this->bufferSize = $bufferSize;
        $this->buffer = [];
        $this->currentBufferSize = 0;
    }

    /**
     * @return int
     */
    public function getBufferSize()
    {
        return $this->bufferSize;
    }

    /**
     * Timeout is in milliseconds.
     */
    public function readMessageFrame(string $subscriptionId, int $timeout): ?Frame
    {
        // pop up frame from the buffer
        if (isset($this->buffer[$subscriptionId]) && ($frame = array_shift($this->buffer[$subscriptionId]))) {
            --$this->currentBufferSize;

            return $frame;
        }

        // do nothing when buffer is full
        if ($this->currentBufferSize >= $this->bufferSize) {
            return null;
        }

        $startTime = microtime(true);
        $remainingTimeout = $timeout * 1000;

        while (true) {
            $this->getConnection()->setReadTimeout(0, $remainingTimeout);

            // there is nothing to read
            if (false === $frame = $this->readFrame()) {
                return null;
            }

            if ('MESSAGE' !== $frame->getCommand()) {
                throw new \LogicException(sprintf('Unexpected frame was received: "%s"', $frame->getCommand()));
            }

            $headers = $frame->getHeaders();

            if (false == isset($headers['subscription'])) {
                throw new \LogicException('Got message frame with missing subscription header');
            }

            // frame belongs to another subscription
            if ($headers['subscription'] !== $subscriptionId) {
                $this->buffer[$headers['subscription']][] = $frame;
                ++$this->currentBufferSize;

                $remainingTimeout -= (microtime(true) - $startTime) * 1000000;

                if ($remainingTimeout <= 0) {
                    return null;
                }

                continue;
            }

            return $frame;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect($sync = false)
    {
        parent::disconnect($sync);

        $this->buffer = [];
        $this->currentBufferSize = 0;
    }
}
