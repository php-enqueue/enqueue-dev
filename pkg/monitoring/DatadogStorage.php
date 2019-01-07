<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

use DataDog\BatchedDogStatsd;
use DataDog\DogStatsd;
use Enqueue\Client\Config;
use Enqueue\Dsn\Dsn;

class DatadogStorage implements StatsStorage
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var BatchedDogStatsd
     */
    private $datadog;

    public function __construct($config = 'datadog:')
    {
        if (false === class_exists(DogStatsd::class)) {
            throw new \LogicException('Seems client library is not installed. Please install "datadog/php-datadogstatsd"');
        }

        $this->config = $this->prepareConfig($config);

        if (null === $this->datadog) {
            if (true === filter_var($this->config['batched'], FILTER_VALIDATE_BOOLEAN)) {
                $this->datadog = new BatchedDogStatsd($this->config);
            } else {
                $this->datadog = new DogStatsd($this->config);
            }
        }
    }

    public function pushConsumerStats(ConsumerStats $stats): void
    {
        $queues = $stats->getQueues();
        array_walk($queues, function (string $queue) use ($stats) {
            $tags = [
                'queue' => $queue,
                'consumerId' => $stats->getConsumerId(),
            ];

            if ($stats->getFinishedAtMs()) {
                $values['finishedAtMs'] = $stats->getFinishedAtMs();
            }

            $this->datadog->gauge($this->config['metric.consumers.started'], (int) $stats->isStarted(), 1, $tags);
            $this->datadog->gauge($this->config['metric.consumers.finished'], (int) $stats->isFinished(), 1, $tags);
            $this->datadog->gauge($this->config['metric.consumers.failed'], (int) $stats->isFailed(), 1, $tags);
            $this->datadog->gauge($this->config['metric.consumers.received'], $stats->getReceived(), 1, $tags);
            $this->datadog->gauge($this->config['metric.consumers.acknowledged'], $stats->getAcknowledged(), 1, $tags);
            $this->datadog->gauge($this->config['metric.consumers.rejected'], $stats->getRejected(), 1, $tags);
            $this->datadog->gauge($this->config['metric.consumers.requeued'], $stats->getRejected(), 1, $tags);
            $this->datadog->gauge($this->config['metric.consumers.memoryUsage'], $stats->getMemoryUsage(), 1, $tags);
        });
    }

    public function pushSentMessageStats(SentMessageStats $stats): void
    {
        $tags = [
            'destination' => $stats->getDestination(),
        ];

        $properties = $stats->getProperties();
        if (false === empty($properties[Config::TOPIC])) {
            $tags['topic'] = $properties[Config::TOPIC];
        }

        if (false === empty($properties[Config::COMMAND])) {
            $tags['command'] = $properties[Config::COMMAND];
        }

        $this->datadog->increment($this->config['metric.messages.sent'], 1, $tags);
    }

    public function pushConsumedMessageStats(ConsumedMessageStats $stats): void
    {
        $tags = [
            'queue' => $stats->getQueue(),
            'status' => $stats->getStatus(),
        ];

        if (ConsumedMessageStats::STATUS_FAILED === $stats->getStatus()) {
            $this->datadog->increment($this->config['metric.messages.failed'], 1, $tags);
        }

        if ($stats->isRedelivered()) {
            $this->datadog->increment($this->config['metric.messages.redelivered'], 1, $tags);
        }

        $runtime = $stats->getTimestampMs() - $stats->getReceivedAtMs();
        $this->datadog->histogram($this->config['metric.messages.consumed'], $runtime, 1, $tags);
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('datadog' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "datadog"',
                $dsn->getSchemeProtocol()
            ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'global_tags' => $dsn->getString('global_tags'),
            'batched' => $dsn->getString('batched'),
            'metric.messages.sent' => $dsn->getString('metric.messages.sent'),
            'metric.messages.consumed' => $dsn->getString('metric.messages.consumed'),
            'metric.messages.redelivered' => $dsn->getString('metric.messages.redelivered'),
            'metric.messages.failed' => $dsn->getString('metric.messages.failed'),
            'metric.consumers.started' => $dsn->getString('metric.consumers.started'),
            'metric.consumers.finished' => $dsn->getString('metric.consumers.finished'),
            'metric.consumers.failed' => $dsn->getString('metric.consumers.failed'),
            'metric.consumers.received' => $dsn->getString('metric.consumers.received'),
            'metric.consumers.acknowledged' => $dsn->getString('metric.consumers.acknowledged'),
            'metric.consumers.rejected' => $dsn->getString('metric.consumers.rejected'),
            'metric.consumers.requeued' => $dsn->getString('metric.consumers.requeued'),
            'metric.consumers.memoryUsage' => $dsn->getString('metric.consumers.memoryUsage'),
        ]), function ($value) {
            return null !== $value;
        });
    }

    /**
     * @param $config
     *
     * @return array
     */
    private function prepareConfig($config): array
    {
        if (empty($config)) {
            $config = $this->parseDsn('datadog:');
        } elseif (\is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (\is_array($config)) {
            $config = empty($config['dsn']) ? $config : $this->parseDsn($config['dsn']);
        } elseif ($config instanceof DogStatsd) {
            $this->datadog = $config;
            $config = [];
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        return array_replace([
            'host' => 'localhost',
            'port' => 8125,
            'batched' => true,
            'metric.messages.sent' => 'enqueue.messages.sent',
            'metric.messages.consumed' => 'enqueue.messages.consumed',
            'metric.messages.redelivered' => 'enqueue.messages.redelivered',
            'metric.messages.failed' => 'enqueue.messages.failed',
            'metric.consumers.started' => 'enqueue.consumers.started',
            'metric.consumers.finished' => 'enqueue.consumers.finished',
            'metric.consumers.failed' => 'enqueue.consumers.failed',
            'metric.consumers.received' => 'enqueue.consumers.received',
            'metric.consumers.acknowledged' => 'enqueue.consumers.acknowledged',
            'metric.consumers.rejected' => 'enqueue.consumers.rejected',
            'metric.consumers.requeued' => 'enqueue.consumers.requeued',
            'metric.consumers.memoryUsage' => 'enqueue.consumers.memoryUsage',
        ], $config);
    }
}
