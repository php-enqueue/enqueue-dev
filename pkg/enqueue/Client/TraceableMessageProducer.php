<?php
namespace Enqueue\Client;

class TraceableMessageProducer implements MessageProducerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var array
     */
    protected $traces = [];

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->messageProducer->send($topic, $message);

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
