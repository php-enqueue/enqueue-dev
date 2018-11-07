<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

use Enqueue\Client\PostSend;
use Enqueue\Client\PostSendExtensionInterface;
use Interop\Queue\Topic;
use Psr\Log\LoggerInterface;

class ClientMonitoringExtension implements PostSendExtensionInterface
{
    /**
     * @var StatsStorage
     */
    private $storage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(StatsStorage $storage, LoggerInterface $logger)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function onPostSend(PostSend $context): void
    {
        $timestampMs = (int) (microtime(true) * 1000);

        $destination = $context->getTransportDestination() instanceof Topic
            ? $context->getTransportDestination()->getTopicName()
            : $context->getTransportDestination()->getQueueName()
        ;

        $stats = new SentMessageStats(
            $timestampMs,
            $destination,
            $context->getTransportMessage()->getMessageId(),
            $context->getTransportMessage()->getCorrelationId(),
            $context->getTransportMessage()->getHeaders(),
            $context->getTransportMessage()->getProperties()
        );

        $this->safeCall(function () use ($stats) {
            $this->storage->pushSentMessageStats($stats);
        });
    }

    private function safeCall(callable $fun)
    {
        try {
            return call_user_func($fun);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ClientMonitoringExtension] Push to storage failed: %s', $e->getMessage()));
        }

        return null;
    }
}
