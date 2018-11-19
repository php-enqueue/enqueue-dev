<?php

namespace Enqueue\Consumption\Context;

use Enqueue\Consumption\Result;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;

final class MessageReceived
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
     * @var Processor
     */
    private $processor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $receivedAt;

    /**
     * @var Result|null
     */
    private $result;

    public function __construct(
        Context $context,
        Consumer $consumer,
        Message $message,
        Processor $processor,
        int $receivedAt,
        LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->consumer = $consumer;
        $this->message = $message;
        $this->processor = $processor;
        $this->receivedAt = $receivedAt;
        $this->logger = $logger;
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

    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    public function changeProcessor(Processor $processor): void
    {
        $this->processor = $processor;
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
