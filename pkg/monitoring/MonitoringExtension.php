<?php

namespace Enqueue\Monitoring;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\EndExtensionInterface;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Enqueue\Consumption\MessageResultExtensionInterface;
use Enqueue\Consumption\PreConsumeExtensionInterface;
use Enqueue\Consumption\PreSubscribeExtensionInterface;
use Enqueue\Consumption\ProcessorExceptionExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\StartExtensionInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class MonitoringExtension implements StartExtensionInterface, PreSubscribeExtensionInterface, PreConsumeExtensionInterface, EndExtensionInterface, ProcessorExceptionExtensionInterface, MessageReceivedExtensionInterface, MessageResultExtensionInterface
{
    /**
     * @var StatsStorage
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

    public function __construct(StatsStorage $storage)
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
        // send started only once
        $isStarted = false;
        if (0 === $this->startedAtMs) {
            $isStarted = true;
            $this->startedAtMs = $context->getStartTime();
        }

        // send stats event only once per period
        $time = time();
        if (($time - $this->lastStatsAt) > $this->updateStatsPeriod) {
            $this->lastStatsAt = $time;

            $event = new ConsumerStats(
                $this->consumerId,
                $this->getNowMs(),
                $this->startedAtMs,
                null,
                $isStarted,
                false,
                false,
                $this->queues,
                $this->received,
                $this->acknowledged,
                $this->rejected,
                $this->requeued,
                $this->getMemoryUsage(),
                $this->getSystemLoad()
            );

            $this->safeCall(function () use ($event) {
                $this->storage->pushConsumerStats($event);
            }, $context->getLogger());
        }
    }

    public function onEnd(End $context): void
    {
        $event = new ConsumerStats(
            $this->consumerId,
            $this->getNowMs(),
            $this->startedAtMs,
            $context->getEndTime(),
            false,
            true,
            false,
            $this->queues,
            $this->received,
            $this->acknowledged,
            $this->rejected,
            $this->requeued,
            $this->getMemoryUsage(),
            $this->getSystemLoad()
        );

        $this->safeCall(function () use ($event) {
            $this->storage->pushConsumerStats($event);
        }, $context->getLogger());
    }

    public function onProcessorException(ProcessorException $context): void
    {
        $timeMs = $this->getNowMs();

        $event = new ConsumedMessageStats(
            $this->consumerId,
            $timeMs,
            $context->getReceivedAt(),
            $context->getConsumer()->getQueue()->getQueueName(),
            $context->getMessage()->getMessageId(),
            $context->getMessage()->getCorrelationId(),
            $context->getMessage()->getHeaders(),
            $context->getMessage()->getProperties(),
            $context->getMessage()->isRedelivered(),
            ConsumedMessageStats::STATUS_FAILED,
            get_class($context->getException()),
            $context->getException()->getMessage(),
            $context->getException()->getCode(),
            $context->getException()->getFile(),
            $context->getException()->getLine(),
            $context->getException()->getTraceAsString()
        );

        $this->safeCall(function () use ($event) {
            $this->storage->pushConsumedMessageStats($event);
        }, $context->getLogger());

        // priority of this extension must be the lowest and
        // if result is null we emit consumer stopped event here
        if (null === $context->getResult()) {
            $event = new ConsumerStats(
                $this->consumerId,
                $timeMs,
                $this->startedAtMs,
                $timeMs,
                false,
                true,
                true,
                $this->queues,
                $this->received,
                $this->acknowledged,
                $this->rejected,
                $this->requeued,
                $this->getMemoryUsage(),
                $this->getSystemLoad(),
                get_class($context->getException()),
                $context->getException()->getMessage(),
                $context->getException()->getCode(),
                $context->getException()->getFile(),
                $context->getException()->getLine(),
                $context->getException()->getTraceAsString()
            );

            $this->safeCall(function () use ($event) {
                $this->storage->pushConsumerStats($event);
            }, $context->getLogger());
        }
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        ++$this->received;
    }

    public function onResult(MessageResult $context): void
    {
        $timeMs = $this->getNowMs();

        switch ($context->getResult()) {
            case Result::ACK:
            case Result::ALREADY_ACKNOWLEDGED:
                $this->acknowledged++;
                $status = ConsumedMessageStats::STATUS_ACK;
                break;
            case Result::REJECT:
                $this->rejected++;
                $status = ConsumedMessageStats::STATUS_REJECTED;
                break;
            case Result::REQUEUE:
                $this->requeued++;
                $status = ConsumedMessageStats::STATUS_REQUEUED;
                break;
            default:
                throw new \LogicException();
        }

        $event = new ConsumedMessageStats(
            $this->consumerId,
            $timeMs,
            $context->getReceivedAt(),
            $context->getConsumer()->getQueue()->getQueueName(),
            $context->getMessage()->getMessageId(),
            $context->getMessage()->getCorrelationId(),
            $context->getMessage()->getHeaders(),
            $context->getMessage()->getProperties(),
            $context->getMessage()->isRedelivered(),
            $status
        );

        $this->safeCall(function () use ($event) {
            $this->storage->pushConsumedMessageStats($event);
        }, $context->getLogger());

        // send stats event only once per period
        $time = time();
        if (($time - $this->lastStatsAt) > $this->updateStatsPeriod) {
            $this->lastStatsAt = $time;

            $event = new ConsumerStats(
                $this->consumerId,
                $timeMs,
                $this->startedAtMs,
                null,
                false,
                false,
                false,
                $this->queues,
                $this->received,
                $this->acknowledged,
                $this->rejected,
                $this->requeued,
                $this->getMemoryUsage(),
                $this->getSystemLoad()
            );

            $this->safeCall(function () use ($event) {
                $this->storage->pushConsumerStats($event);
            }, $context->getLogger());
        }
    }

    private function getNowMs(): int
    {
        return (int) (microtime(true) * 1000);
    }

    private function getMemoryUsage(): int
    {
        return memory_get_usage(true);
    }

    private function getSystemLoad(): float
    {
        return sys_getloadavg()[0];
    }

    private function safeCall(callable $fun, LoggerInterface $logger)
    {
        try {
            return call_user_func($fun);
        } catch (\Throwable $e) {
            $logger->error(sprintf('[MonitoringExtension] Push to storage failed: %s', $e->getMessage()));
        }

        return null;
    }
}
