<?php
namespace Enqueue\Consumption;

use Enqueue\Psr\Consumer;
use Enqueue\Psr\Context as PsrContext;
use Enqueue\Psr\Message;
use Enqueue\Psr\Queue;
use Enqueue\Consumption\Exception\IllegalContextModificationException;
use Psr\Log\LoggerInterface;

class Context
{
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @var \Enqueue\Psr\Consumer
     */
    private $psrConsumer;

    /**
     * @var MessageProcessorInterface
     */
    private $messageProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Message
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
     * @var \Enqueue\Psr\Queue
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
     * @return \Enqueue\Psr\Message
     */
    public function getPsrMessage()
    {
        return $this->psrMessage;
    }

    /**
     * @param Message $psrMessage
     */
    public function setPsrMessage(Message $psrMessage)
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
     * @return Consumer
     */
    public function getPsrConsumer()
    {
        return $this->psrConsumer;
    }

    /**
     * @param Consumer $psrConsumer
     */
    public function setPsrConsumer(Consumer $psrConsumer)
    {
        if ($this->psrConsumer) {
            throw new IllegalContextModificationException('The message consumer could be set once');
        }

        $this->psrConsumer = $psrConsumer;
    }

    /**
     * @return MessageProcessorInterface
     */
    public function getMessageProcessor()
    {
        return $this->messageProcessor;
    }

    /**
     * @param MessageProcessorInterface $messageProcessor
     */
    public function setMessageProcessor(MessageProcessorInterface $messageProcessor)
    {
        if ($this->messageProcessor) {
            throw new IllegalContextModificationException('The message processor could be set once');
        }

        $this->messageProcessor = $messageProcessor;
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
     * @return \Enqueue\Psr\Queue
     */
    public function getPsrQueue()
    {
        return $this->psrQueue;
    }

    /**
     * @param \Enqueue\Psr\Queue $psrQueue
     */
    public function setPsrQueue(Queue $psrQueue)
    {
        if ($this->psrQueue) {
            throw new IllegalContextModificationException('The queue modification is not allowed');
        }

        $this->psrQueue = $psrQueue;
    }
}
