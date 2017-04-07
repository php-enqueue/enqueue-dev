<?php

namespace Enqueue\Client;

class TraceableProducer implements MessageProducerInterface
{
    /**
     * @var array
     */
    protected $traces = [];
    /**
     * @var MessageProducerInterface
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
    public function send($topic, $message)
    {
        $this->producer->send($topic, $message);

        $trace = [
            'topic' => $topic,
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
}
