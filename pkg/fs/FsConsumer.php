<?php

namespace Enqueue\Fs;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

class FsConsumer implements PsrConsumer
{
    /**
     * @var FsDestination
     */
    private $destination;

    /**
     * @var FsContext
     */
    private $context;

    /**
     * @var int
     */
    private $preFetchCount;

    /**
     * @var FsMessage[]
     */
    private $preFetchedMessages;

    /**
     * @var int microseconds
     */
    private $pollingInterval = 100000;

    /**
     * @param FsContext     $context
     * @param FsDestination $destination
     * @param int           $preFetchCount
     */
    public function __construct(FsContext $context, FsDestination $destination, $preFetchCount)
    {
        $this->context = $context;
        $this->destination = $destination;
        $this->preFetchCount = $preFetchCount;

        $this->preFetchedMessages = [];
    }

    /**
     * Set polling interval in milliseconds.
     *
     * @param int $msec
     */
    public function setPollingInterval($msec)
    {
        $this->pollingInterval = $msec * 1000;
    }

    /**
     * Get polling interval in milliseconds.
     *
     * @return int
     */
    public function getPollingInterval()
    {
        return (int) $this->pollingInterval / 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @return FsDestination
     */
    public function getQueue()
    {
        return $this->destination;
    }

    /**
     * {@inheritdoc}
     *
     * @return FsMessage|null
     */
    public function receive($timeout = 0)
    {
        $timeout /= 1000;
        $startAt = microtime(true);

        while (true) {
            $message = $this->receiveNoWait();

            if ($message) {
                return $message;
            }

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return;
            }

            usleep($this->pollingInterval);

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function receiveNoWait()
    {
        if ($this->preFetchedMessages) {
            return array_shift($this->preFetchedMessages);
        }

        $this->context->workWithFile($this->destination, 'c+', function (FsDestination $destination, $file) {
            $count = $this->preFetchCount;
            while ($count) {
                $frame = $this->readFrame($file, 1);

                //guards
                if ($frame && false == ('|' == $frame[0] || ' ' == $frame[0])) {
                    throw new \LogicException(sprintf('The frame could start from either " " or "|". The malformed frame starts with "%s".', $frame[0]));
                }
                if (0 !== $reminder = strlen($frame) % 64) {
                    throw new \LogicException(sprintf('The frame size is "%d" and it must divide exactly to 64 but it leaves a reminder "%d".', strlen($frame), $reminder));
                }

                ftruncate($file, fstat($file)['size'] - strlen($frame));
                rewind($file);

                $rawMessage = substr(trim($frame), 1);

                if ($rawMessage) {
                    try {
                        $fetchedMessage = FsMessage::jsonUnserialize($rawMessage);
                        $expireAt = $fetchedMessage->getHeader('x-expire-at');
                        if ($expireAt && $expireAt - microtime(true) < 0) {
                            // message has expired, just drop it.
                            return;
                        }

                        $this->preFetchedMessages[] = $fetchedMessage;
                    } catch (\Exception $e) {
                        throw new \LogicException(sprintf("Cannot decode json message '%s'", $rawMessage), null, $e);
                    }
                } else {
                    return;
                }

                --$count;
            }
        });

        if ($this->preFetchedMessages) {
            return array_shift($this->preFetchedMessages);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(PsrMessage $message)
    {
        // do nothing. fs transport always works in auto ack mode
    }

    /**
     * {@inheritdoc}
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, FsMessage::class);

        // do nothing on reject. fs transport always works in auto ack mode

        if ($requeue) {
            $this->context->createProducer()->send($this->destination, $message);
        }
    }

    /**
     * @return int
     */
    public function getPreFetchCount()
    {
        return $this->preFetchCount;
    }

    /**
     * @param int $preFetchCount
     */
    public function setPreFetchCount($preFetchCount)
    {
        $this->preFetchCount = $preFetchCount;
    }

    /**
     * @param resource $file
     * @param int      $frameNumber
     *
     * @return string
     */
    private function readFrame($file, $frameNumber)
    {
        $frameSize = 64;
        $offset = $frameNumber * $frameSize;

        fseek($file, -$offset, SEEK_END);
        $frame = fread($file, $frameSize);
        if ('' == $frame) {
            return '';
        }

        if (false !== strpos($frame, '|{')) {
            return $frame;
        }

        $previousFrame = $this->readFrame($file, $frameNumber + 1);

        if ('|' === substr($previousFrame, -1) && '{' === $frame[0]) {
            $matched = [];
            if (false === preg_match('/\ *?\|$/', $previousFrame, $matched)) {
                throw new \LogicException('Something went completely wrong.');
            }

            return $matched[0].$frame;
        }

        return $previousFrame.$frame;
    }
}
