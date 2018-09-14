<?php

declare(strict_types=1);

namespace Enqueue\Client;

use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;

interface DriverInterface
{
    public function createTransportMessage(Message $message): PsrMessage;

    public function createClientMessage(PsrMessage $message): Message;

    public function sendToRouter(Message $message): void;

    public function sendToProcessor(Message $message): void;

    public function createQueue(string $queueName): PsrQueue;

    /**
     * Prepare broker for work.
     * Creates all required queues, exchanges, topics, bindings etc.
     */
    public function setupBroker(LoggerInterface $logger = null): void;

    public function getConfig(): Config;
}
