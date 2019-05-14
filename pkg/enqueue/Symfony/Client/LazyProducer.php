<?php

declare(strict_types=1);

namespace Enqueue\Symfony\Client;

use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\Promise;
use Psr\Container\ContainerInterface;

class LazyProducer implements ProducerInterface
{
    private $container;

    private $producerId;

    public function __construct(ContainerInterface $container, string $producerId)
    {
        $this->container = $container;
        $this->producerId = $producerId;
    }

    public function sendEvent(string $topic, $message): void
    {
        $this->getRealProducer()->sendEvent($topic, $message);
    }

    public function sendCommand(string $command, $message, bool $needReply = false): ?Promise
    {
        return $this->getRealProducer()->sendCommand($command, $message, $needReply);
    }

    private function getRealProducer(): ProducerInterface
    {
        return $this->container->get($this->producerId);
    }
}
