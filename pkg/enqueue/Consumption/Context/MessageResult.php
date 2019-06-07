<?php

namespace Enqueue\Consumption\Context;

use Enqueue\Consumption\Result;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Psr\Log\LoggerInterface;

final class MessageResult
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var Result|string|object|null
     */
    private $result;

    /**
     * @var int
     */
    private $receivedAt;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Context $context, Consumer $consumer, Message $message, $result, int $receivedAt, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->consumer = $consumer;
        $this->message = $message;
        $this->logger = $logger;
        $this->result = $result;
        $this->receivedAt = $receivedAt;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getConsumer(): Consumer
    {
        return $this->consumer;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getReceivedAt(): int
    {
        return $this->receivedAt;
    }

    /**
     * @return Result|object|string|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param Result|string|object|null $result
     */
    public function changeResult($result): void
    {
        $this->result = $result;
    }
}
