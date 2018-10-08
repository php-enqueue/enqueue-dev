<?php

namespace Enqueue\Consumption\Context;

use Enqueue\Consumption\Result;
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

    public function __construct(Context $context, Message $message, $result, int $receivedAt, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->message = $message;
        $this->logger = $logger;
        $this->result = $result;
        $this->receivedAt = $receivedAt;
    }

    public function getContext(): Context
    {
        return $this->context;
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
     * @return Result|null|object|string
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
