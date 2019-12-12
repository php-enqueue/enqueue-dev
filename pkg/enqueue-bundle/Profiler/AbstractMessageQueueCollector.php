<?php

namespace Enqueue\Bundle\Profiler;

use Enqueue\Client\MessagePriority;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Util\JSON;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

abstract class AbstractMessageQueueCollector extends DataCollector
{
    /**
     * @var ProducerInterface
     */
    protected $producers;

    public function addProducer(string $name, ProducerInterface $producer): void
    {
        $this->producers[$name] = $producer;
    }

    public function getCount(): int
    {
        $count = 0;
        foreach ($this->data as $name => $messages) {
            $count += count($messages);
        }

        return $count;
    }

    /**
     * @return array
     */
    public function getSentMessages()
    {
        return $this->data;
    }

    /**
     * @param string $priority
     *
     * @return string
     */
    public function prettyPrintPriority($priority)
    {
        $map = [
            MessagePriority::VERY_LOW => 'very low',
            MessagePriority::LOW => 'low',
            MessagePriority::NORMAL => 'normal',
            MessagePriority::HIGH => 'high',
            MessagePriority::VERY_HIGH => 'very high',
        ];

        return isset($map[$priority]) ? $map[$priority] : $priority;
    }

    /**
     * @param mixed $body
     *
     * @return string
     */
    public function ensureString($body)
    {
        return is_string($body) ? $body : JSON::encode($body);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'enqueue.message_queue';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }

    protected function collectInternal(Request $request, Response $response): void
    {
        $this->data = [];

        foreach ($this->producers as $name => $producer) {
            if ($producer instanceof TraceableProducer) {
                $this->data[$name] = $producer->getTraces();
            }
        }
    }
}
