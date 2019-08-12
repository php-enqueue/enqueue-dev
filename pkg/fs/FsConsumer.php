<?php

declare(strict_types=1);

namespace Enqueue\Fs;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class FsConsumer implements Consumer
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
     * In milliseconds.
     *
     * @var int
     */
    private $pollingInterval = 100;

    public function __construct(FsContext $context, FsDestination $destination, int $preFetchCount)
    {
        $this->context = $context;
        $this->destination = $destination;
        $this->preFetchCount = $preFetchCount;

        $this->preFetchedMessages = [];
    }

    /**
     * Set polling interval in milliseconds.
     */
    public function setPollingInterval(int $msec): void
    {
        $this->pollingInterval = $msec;
    }

    /**
     * Get polling interval in milliseconds.
     */
    public function getPollingInterval(): int
    {
        return $this->pollingInterval;
    }

    /**
     * @return FsDestination
     */
    public function getQueue(): Queue
    {
        return $this->destination;
    }

    /**
     * @return FsMessage
     */
    public function receive(int $timeout = 0): ?Message
    {
        $timeout /= 1000;
        $startAt = microtime(true);

        while (true) {
            $message = $this->receiveNoWait();

            if ($message) {
                return $message;
            }

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return null;
            }

            usleep($this->pollingInterval * 1000);

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return null;
            }
        }
    }

    /**
     * @return FsMessage
     */
    public function receiveNoWait(): ?Message
    {
        if ($this->preFetchedMessages) {
            return array_shift($this->preFetchedMessages);
        }

        $this->context->workWithFile($this->destination, 'c+', function (FsDestination $destination, $file) {
            $count = $this->preFetchCount;
            while ($count) {
                $frame = $this->readFrame($file);

                //guards
                if ($frame && false == ('|' == $frame[0] || ' ' == $frame[0])) {
                    throw new \LogicException(sprintf('The frame could start from either " " or "|". The malformed frame starts with "%s".', $frame[0]));
                }
                if (0 !== $reminder = strlen($frame) % 64) {
                    throw new \LogicException(sprintf('The frame size is "%d" and it must divide exactly to 64 but it leaves a reminder "%d".', strlen($frame), $reminder));
                }

                fseek($file, strlen($frame), SEEK_SET);
                $temp = tmpfile();
                stream_copy_to_stream($file, $temp);
                rewind($temp);
                ftruncate($file, 0);
                rewind($file);
                stream_copy_to_stream($temp, $file);
                rewind($file);
                fclose($temp);

                $rawMessage = str_replace('\|\{', '|{', $frame);
                $rawMessage = substr(trim($rawMessage), 1);

                if ($rawMessage) {
                    try {
                        $fetchedMessage = FsMessage::jsonUnserialize($rawMessage);
                        $expireAt = $fetchedMessage->getHeader('x-expire-at');
                        if ($expireAt && $expireAt - microtime(true) < 0) {
                            // message has expired, just drop it.
                            return null;
                        }

                        $this->preFetchedMessages[] = $fetchedMessage;
                    } catch (\Exception $e) {
                        throw new \LogicException(sprintf("Cannot decode json message '%s'", $rawMessage), 0, $e);
                    }
                } else {
                    return null;
                }

                --$count;
            }
        });

        if ($this->preFetchedMessages) {
            return array_shift($this->preFetchedMessages);
        }

        return null;
    }

    public function acknowledge(Message $message): void
    {
        // do nothing. fs transport always works in auto ack mode
    }

    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, FsMessage::class);

        // do nothing on reject. fs transport always works in auto ack mode

        if ($requeue) {
            $this->context->createProducer()->send($this->destination, $message);
        }
    }

    public function getPreFetchCount(): int
    {
        return $this->preFetchCount;
    }

    public function setPreFetchCount(int $preFetchCount): void
    {
        $this->preFetchCount = $preFetchCount;
    }

    /**
     * @param resource $file
     *
     * @return string
     */
    private function readFrame($file): string
    {
        $frameSize = 64;
        $needle = '|{';
        $frame = '';

        while (!feof($file)) {
            $frame .= fread($file, $frameSize);

            if (substr_count($frame, $needle) > 1) {
                $pos = strpos($frame, $needle);
                $pos = strpos($frame, $needle, $pos + 1);
                return rtrim(substr($frame, 0, $pos));
            }
        }

        if (1 == substr_count($frame, $needle)) {
            return $frame;
        }

        return '';
    }
}
