<?php

namespace Enqueue\Consumption\Context;

use Enqueue\Consumption\Result;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Psr\Log\LoggerInterface;

final class ProcessorException
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
     * @var \Exception
     */
    private $exception;

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

    public function __construct(Context $context, Consumer $consumer, Message $message, \Exception $exception, int $receivedAt, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->consumer = $consumer;
        $this->message = $message;
        $this->exception = $exception;
        $this->logger = $logger;
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

    public function getException(): \Exception
    {
        return $this->exception;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getReceivedAt(): int
    {
        return $this->receivedAt;
    }

    public function getResult(): ?Result
    {
        return $this->result;
    }

    public function setResult(Result $result): void
    {
        $this->result = $result;
    }
}
