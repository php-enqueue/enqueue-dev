<?php

namespace Enqueue\Consumption\Context;

use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Psr\Log\LoggerInterface;

final class PostMessageReceived
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $receivedAt;

    /**
     * @var Result|string|object|null
     */
    private $result;

    /**
     * @var bool
     */
    private $executionInterrupted;

    public function __construct(
        Context $context,
        Message $message,
        $result,
        int $receivedAt,
        LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->message = $message;
        $this->result = $result;
        $this->receivedAt = $receivedAt;
        $this->logger = $logger;

        $this->executionInterrupted = false;
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

    public function isExecutionInterrupted(): bool
    {
        return $this->executionInterrupted;
    }

    public function interruptExecution(): void
    {
        $this->executionInterrupted = true;
    }
}
