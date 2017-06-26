<?php

namespace Enqueue\Client;

class TraceableProducer implements ProducerInterface
{
    /**
     * @var array
     */
    protected $traces = [];
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param ProducerInterface $producer
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function sendEvent($topic, $message)
    {
        $this->producer->sendEvent($topic, $message);

        $this->collectTrace($topic, null, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function sendCommand($command, $message, $needReply = false)
    {
        $result = $this->producer->sendCommand($command, $message, $needReply);

        $this->collectTrace(Config::COMMAND_TOPIC, $command, $message);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->sendEvent($topic, $message);
    }

    /**
     * @param string $topic
     *
     * @return array
     */
    public function getTopicTraces($topic)
    {
        $topicTraces = [];
        foreach ($this->traces as $trace) {
            if ($topic == $trace['topic']) {
                $topicTraces[] = $trace;
            }
        }

        return $topicTraces;
    }

    /**
     * @param string $command
     *
     * @return array
     */
    public function getCommandTraces($command)
    {
        $commandTraces = [];
        foreach ($this->traces as $trace) {
            if ($command == $trace['command']) {
                $commandTraces[] = $trace;
            }
        }

        return $commandTraces;
    }

    /**
     * @return array
     */
    public function getTraces()
    {
        return $this->traces;
    }

    public function clearTraces()
    {
        $this->traces = [];
    }

    /**
     * @param string|null $topic
     * @param string|null $command
     * @param mixed       $message
     */
    private function collectTrace($topic, $command, $message)
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
