<?php

namespace Enqueue\Client;

use Enqueue\Rpc\Promise;

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

    public function __construct(ProducerInterface $realProducer)
    {
        $this->realProducer = $realProducer;

        $this->events = new \SplQueue();
        $this->commands = new \SplQueue();
    }

    public function sendCommand(string $command, $message, bool $needReply = false): ?Promise
    {
        if ($needReply) {
            return $this->realProducer->sendCommand($command, $message, $needReply);
        }

        $this->commands->enqueue([$command, $message]);

        return null;
    }

    public function sendEvent(string $topic, $message): void
    {
        $this->events->enqueue([$topic, $message]);
    }

    /**
     * When it is called it sends all previously queued messages.
     */
    public function flush(): void
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
