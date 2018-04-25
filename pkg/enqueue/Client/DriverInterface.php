<?php

namespace Enqueue\Client;

use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;

interface DriverInterface
{
    /**
     * @param Message $message
     *
     * @return PsrMessage
     */
    public function createTransportMessage(Message $message);

    /**
     * @param PsrMessage $message
     *
     * @return Message
     */
    public function createClientMessage(PsrMessage $message);

    /**
     * @param Message $message
     */
    public function sendToRouter(Message $message);

    /**
     * @param Message $message
     */
    public function sendToProcessor(Message $message);

    /**
     * @param string $queueName
     *
     * @return PsrQueue
     */
    public function createQueue($queueName);

    /**
     * Creates all required queues, exchanges, topics, bindings on broker side.
     *
     * @param LoggerInterface $logger
     */
    public function setupBroker(LoggerInterface $logger = null);

    /**
     * @return Config
     */
    public function getConfig();
}
