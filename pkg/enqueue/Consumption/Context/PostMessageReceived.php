<?php

namespace Enqueue\Consumption\Context;

use Enqueue\Consumption\Result;
use Interop\Queue\Consumer;
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
     * @var Consumer
     */
    private $consumer;

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

    /**
     * @var int
     */
    private $exitStatus;

    public function __construct(
        Context $context,
        Consumer $consumer,
        Message $message,
        $result,
        int $receivedAt,
        LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->consumer = $consumer;
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

    public function getExitStatus(): ?int
    {
        return $this->exitStatus;
    }

    public function isExecutionInterrupted(): bool
    {
        return $this->executionInterrupted;
    }

    public function interruptExecution(?int $exitStatus = null): void
    {
        $this->exitStatus = $exitStatus;
        $this->executionInterrupted = true;
    }
}
