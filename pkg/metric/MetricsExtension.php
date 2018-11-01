<?php

namespace Enqueue\Metric;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\Consumer;
use Ramsey\Uuid\Uuid;

class MetricsExtension implements ExtensionInterface
{
    /**
     * @var EventStorage
     */
    private $storage;

    /**
     * @var int
     */
    private $updateStatsPeriod;

    /**
     * @var string[]
     */
    private $queues;

    /**
     * @var string
     */
    private $consumerId;

    /**
     * @var int
     */
    private $received;

    /**
     * @var int
     */
    private $acknowledged;

    /**
     * @var int
     */
    private $rejected;

    /**
     * @var int
     */
    private $requeued;

    /**
     * @var int
     */
    private $startedAtMs;

    /**
     * @var int
     */
    private $lastStatsAt;

    public function __construct(EventStorage $storage)
    {
        $this->storage = $storage;
        $this->updateStatsPeriod = 60;
    }

    public function onStart(Start $context): void
    {
        $this->consumerId = Uuid::uuid4()->toString();

        $this->queues = [];

        $this->startedAtMs = 0;
        $this->lastStatsAt = 0;

        $this->received = 0;
        $this->acknowledged = 0;
        $this->rejected = 0;
        $this->requeued = 0;
    }

    public function onPreSubscribe(PreSubscribe $context): void
    {
        $this->queues[] = $context->getConsumer()->getQueue()->getQueueName();
    }

    public function onPreConsume(PreConsume $context): void
    {
        $time = time();

        // send started only once
        if (0 === $this->startedAtMs) {
            $this->startedAtMs = $context->getStartTime();

            $event = new ConsumerStarted(
                $this->consumerId,
                $this->startedAtMs,
                $this->queues
            );

            $this->storage->onConsumerStarted($event);
        }

        // send stats event only once per period
        if (($time - $this->lastStatsAt) > $this->updateStatsPeriod) {
            $this->lastStatsAt = $time;

            $memoryUsage = memory_get_usage(true);
            $systemLoad = sys_getloadavg()[0];

            $event = new ConsumerStats(
                $this->consumerId,
                (int) (microtime(true) * 1000),
                $this->queues,
                $this->startedAtMs,
                $this->received,
                $this->acknowledged,
                $this->rejected,
                $this->requeued,
                $memoryUsage,
                $systemLoad
            );

            $this->storage->onConsumerStats($event);
        }
    }

    public function onEnd(End $context): void
    {
        $event = new ConsumerStopped(
            $this->consumerId,
            $context->getEndTime(),
            $this->queues,
            $context->getStartTime(),
            $this->received,
            $this->acknowledged,
            $this->rejected,
            $this->requeued
        );

        $this->storage->onConsumerStopped($event);
    }

    public function onProcessorException(ProcessorException $context): void
    {
        $timeMs = (int) (microtime(true) * 1000);

        $event = new MessageStats(
            $this->consumerId,
            $timeMs,
            $context->getReceivedAt(),
            $context->getConsumer()->getQueue()->getQueueName(),
            $context->getMessage()->getHeaders(),
            $context->getMessage()->getProperties(),
            MessageStats::STATUS_FAILED
        );

        $this->storage->onMessageStats($event);

        // priority of this extension must be the lowest and
        // if result is null we emit consumer stopped event here
        if (null === $context->getResult()) {
            $event = new ConsumerStopped(
                $this->consumerId,
                $timeMs,
                $this->queues,
                $this->startedAtMs,
                $this->received,
                $this->acknowledged,
                $this->rejected,
                $this->requeued,
                get_class($context->getException()),
                $context->getException()->getMessage(),
                $context->getException()->getCode(),
                $context->getException()->getFile(),
                $context->getException()->getLine(),
                $context->getException()->getTraceAsString()
            );

            $this->storage->onConsumerStopped($event);
        }
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        $this->received++;
    }

    public function onResult(MessageResult $context): void
    {
        $timeMs = (int) (microtime(true) * 1000);

        switch ($context->getResult()) {
            case Result::ACK:
            case Result::ALREADY_ACKNOWLEDGED:
                $this->acknowledged++;
                $status = MessageStats::STATUS_ACK;
                break;
            case Result::REJECT:
                $this->rejected++;
                $status = MessageStats::STATUS_REJECTED;
                break;
            case Result::REQUEUE:
                $this->requeued++;
                $status = MessageStats::STATUS_REQUEUED;
                break;
            default:
                throw new \LogicException();
        }

        $event =  new MessageStats(
            $this->consumerId,
            $timeMs,
            $context->getReceivedAt(),
            $context->getConsumer()->getQueue()->getQueueName(),
            $context->getMessage()->getHeaders(),
            $context->getMessage()->getProperties(),
            $status
        );

        $this->storage->onMessageStats($event);
    }

    public function onPostConsume(PostConsume $context): void
    {

    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {

    }

    public function onInitLogger(InitLogger $context): void
    {

    }
}
