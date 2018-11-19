<?php

namespace Enqueue\Client;

use Enqueue\Rpc\Promise;

final class TraceableProducer implements ProducerInterface
{
    /**
     * @var array
     */
    private $traces = [];

    /**
     * @var ProducerInterface
     */
    private $producer;

    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    public function sendEvent(string $topic, $message): void
    {
        $this->producer->sendEvent($topic, $message);

        $this->collectTrace($topic, null, $message);
    }

    public function sendCommand(string $command, $message, bool $needReply = false): ?Promise
    {
        $result = $this->producer->sendCommand($command, $message, $needReply);

        $this->collectTrace(null, $command, $message);

        return $result;
    }

    public function getTopicTraces(string $topic): array
    {
        $topicTraces = [];
        foreach ($this->traces as $trace) {
            if ($topic == $trace['topic']) {
                $topicTraces[] = $trace;
            }
        }

        return $topicTraces;
    }

    public function getCommandTraces(string $command): array
    {
        $commandTraces = [];
        foreach ($this->traces as $trace) {
            if ($command == $trace['command']) {
                $commandTraces[] = $trace;
            }
        }

        return $commandTraces;
    }

    public function getTraces(): array
    {
        return $this->traces;
    }

    public function clearTraces(): void
    {
        $this->traces = [];
    }

    private function collectTrace(string $topic = null, string $command = null, $message): void
    {
        $trace = [
            'topic' => $topic,
            'command' => $command,
            'body' => $message,
            'headers' => [],
            'properties' => [],
            'priority' => null,
            'expire' => null,
            'delay' => null,
            'timestamp' => null,
            'contentType' => null,
            'messageId' => null,
            'sentAt' => (new \DateTime())->format('Y-m-d H:i:s.u'),
        ];

        if ($message instanceof Message) {
            $trace['body'] = $message->getBody();
            $trace['headers'] = $message->getHeaders();
            $trace['properties'] = $message->getProperties();
            $trace['priority'] = $message->getPriority();
            $trace['expire'] = $message->getExpire();
            $trace['delay'] = $message->getDelay();
            $trace['timestamp'] = $message->getTimestamp();
            $trace['contentType'] = $message->getContentType();
            $trace['messageId'] = $message->getMessageId();
        }

        $this->traces[] = $trace;
    }
}
