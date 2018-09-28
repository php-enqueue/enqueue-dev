<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Exception\IllegalContextModificationException;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;
use Interop\Queue\Queue as InteropQueue;
use Psr\Log\LoggerInterface;

class Context
{
    /**
     * @var InteropContext
     */
    private $context;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InteropMessage
     */
    private $interopMessage;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var Result|string
     */
    private $result;

    /**
     * @var InteropQueue
     */
    private $InteropQueue;

    /**
     * @var bool
     */
    private $executionInterrupted;

    /**
     * @param InteropContext $interopContext
     */
    public function __construct(InteropContext $interopContext)
    {
        $this->context = $interopContext;

        $this->executionInterrupted = false;
    }

    /**
     * @return InteropMessage
     */
    public function getInteropMessage()
    {
        return $this->interopMessage;
    }

    /**
     * @param InteropMessage $interopMessage
     */
    public function setInteropMessage(InteropMessage $interopMessage)
    {
        if ($this->interopMessage) {
            throw new IllegalContextModificationException('The message could be set once');
        }

        $this->interopMessage = $interopMessage;
    }

    /**
     * @return InteropContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return Consumer
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * @param Consumer $consumer
     */
    public function setConsumer(Consumer $consumer)
    {
        if ($this->consumer) {
            throw new IllegalContextModificationException('The message consumer could be set once');
        }

        $this->consumer = $consumer;
    }

    /**
     * @return Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @param Processor $processor
     */
    public function setProcessor(Processor $processor)
    {
        if ($this->processor) {
            throw new IllegalContextModificationException('The message processor could be set once');
        }

        $this->processor = $processor;
    }

    /**
     * @return \Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Throwable $exception
     */
    public function setException(\Throwable $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return Result|string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param Result|string $result
     */
    public function setResult($result)
    {
        if ($this->result) {
            throw new IllegalContextModificationException('The result modification is not allowed');
        }

        $this->result = $result;
    }

    /**
     * @return bool
     */
    public function isExecutionInterrupted()
    {
        return $this->executionInterrupted;
    }

    /**
     * @param bool $executionInterrupted
     */
    public function setExecutionInterrupted($executionInterrupted)
    {
        if (false == $executionInterrupted && $this->executionInterrupted) {
            throw new IllegalContextModificationException('The execution once interrupted could not be roll backed');
        }

        $this->executionInterrupted = $executionInterrupted;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        if ($this->logger) {
            throw new IllegalContextModificationException('The logger modification is not allowed');
        }

        $this->logger = $logger;
    }

    /**
     * @return InteropQueue
     */
    public function getInteropQueue()
    {
        return $this->InteropQueue;
    }

    /**
     * @param InteropQueue $InteropQueue
     */
    public function setInteropQueue(InteropQueue $InteropQueue)
    {
        if ($this->InteropQueue) {
            throw new IllegalContextModificationException('The queue modification is not allowed');
        }

        $this->InteropQueue = $InteropQueue;
    }
}
