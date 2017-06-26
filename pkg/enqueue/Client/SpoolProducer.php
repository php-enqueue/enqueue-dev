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
    private $events;

    /**
     * @var array
     */
    private $commands;

    /**
     * @param ProducerInterface $realProducer
     */
    public function __construct(ProducerInterface $realProducer)
    {
        $this->realProducer = $realProducer;

        $this->events = new \SplQueue();
        $this->commands = new \SplQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function sendCommand($command, $message, $needReply = false)
    {
        if ($needReply) {
            return $this->realProducer->sendCommand($command, $message, $needReply);
        }

        $this->commands->enqueue([$command, $message]);
    }

    /**
     * {@inheritdoc}
     */
    public function sendEvent($topic, $message)
    {
        $this->events->enqueue([$topic, $message]);
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->sendEvent($topic, $message);
    }

    /**
     * When it is called it sends all previously queued messages.
     */
    public function flush()
    {
        while (false == $this->events->isEmpty()) {
            list($topic, $message) = $this->events->dequeue();

            $this->realProducer->sendEvent($topic, $message);
        }

        while (false == $this->commands->isEmpty()) {
            list($command, $message) = $this->commands->dequeue();

            $this->realProducer->sendCommand($command, $message);
        }
    }
}
