<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Exception\IllegalContextModificationException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;

class Context
{
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @var PsrConsumer
     */
    private $psrConsumer;

    /**
     * @var PsrProcessor
     */
    private $psrProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PsrMessage
     */
    private $psrMessage;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var Result|string
     */
    private $result;

    /**
     * @var PsrQueue
     */
    private $psrQueue;

    /**
     * @var bool
     */
    private $executionInterrupted;

    /**
     * @param PsrContext $psrContext
     */
    public function __construct(PsrContext $psrContext)
    {
        $this->psrContext = $psrContext;

        $this->executionInterrupted = false;
    }

    /**
     * @return PsrMessage
     */
    public function getPsrMessage()
    {
        return $this->psrMessage;
    }

    /**
     * @param PsrMessage $psrMessage
     */
    public function setPsrMessage(PsrMessage $psrMessage)
    {
        if ($this->psrMessage) {
            throw new IllegalContextModificationException('The message could be set once');
        }

        $this->psrMessage = $psrMessage;
    }

    /**
     * @return PsrContext
     */
    public function getPsrContext()
    {
        return $this->psrContext;
    }

    /**
     * @return PsrConsumer
     */
    public function getPsrConsumer()
    {
        return $this->psrConsumer;
    }

    /**
     * @param PsrConsumer $psrConsumer
     */
    public function setPsrConsumer(PsrConsumer $psrConsumer)
    {
        if ($this->psrConsumer) {
            throw new IllegalContextModificationException('The message consumer could be set once');
        }

        $this->psrConsumer = $psrConsumer;
    }

    /**
     * @return PsrProcessor
     */
    public function getPsrProcessor()
    {
        return $this->psrProcessor;
    }

    /**
     * @param PsrProcessor $psrProcessor
     */
    public function setPsrProcessor(PsrProcessor $psrProcessor)
    {
        if ($this->psrProcessor) {
            throw new IllegalContextModificationException('The message processor could be set once');
        }

        $this->psrProcessor = $psrProcessor;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Exception $exception
     */
    public function setException(\Exception $exception)
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
     * @return PsrQueue
     */
    public function getPsrQueue()
    {
        return $this->psrQueue;
    }

    /**
     * @param PsrQueue $psrQueue
     */
    public function setPsrQueue(PsrQueue $psrQueue)
    {
        if ($this->psrQueue) {
            throw new IllegalContextModificationException('The queue modification is not allowed');
        }

        $this->psrQueue = $psrQueue;
    }
}
