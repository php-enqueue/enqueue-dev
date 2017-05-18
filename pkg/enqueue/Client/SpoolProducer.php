<?php

namespace Enqueue\Client;

class SpoolProducer implements ProducerInterface
{
    /**
     * @var ProducerInterface
     */
    private $realProducer;

    /**
     * @var array
     */
    private $queue;

    /**
     * @param ProducerInterface $realProducer
     */
    public function __construct(ProducerInterface $realProducer)
    {
        $this->realProducer = $realProducer;

        $this->queue = new \SplQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->queue->enqueue([$topic, $message]);
    }

    /**
     * When it is called it sends all previously queued messages.
     */
    public function flush()
    {
        while (false == $this->queue->isEmpty()) {
            list($topic, $message) = $this->queue->dequeue();

            $this->realProducer->send($topic, $message);
        }
    }
}
