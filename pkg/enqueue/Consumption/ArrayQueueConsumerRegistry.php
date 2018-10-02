<?php

namespace Enqueue\Consumption;

class ArrayQueueConsumerRegistry implements QueueConsumerRegistryInterface
{
    /**
     * @var QueueConsumerInterface[]
     */
    private $consumers;

    /**
     * @param QueueConsumerInterface[] $queueConsumers
     */
    public function __construct(array $queueConsumers = [])
    {
        $this->consumers = [];
        array_walk($queueConsumers, function (QueueConsumerInterface $consumer, string $key) {
            $this->consumers[$key] = $consumer;
        });
    }

    public function add(string $name, QueueConsumerInterface $consumer): void
    {
        $this->consumers[$name] = $consumer;
    }

    public function get(string $name): QueueConsumerInterface
    {
        if (false == isset($this->consumers[$name])) {
            throw new \LogicException(sprintf('QueueConsumer was not found, name: "%s".', $name));
        }

        return $this->consumers[$name];
    }
}
