<?php

namespace Enqueue\Client;

use Enqueue\Psr\Message as TransportMessage;
use Enqueue\Psr\Queue;
use Psr\Log\LoggerInterface;

interface DriverInterface
{
    /**
     * @param Message $message
     *
     * @return TransportMessage
     */
    public function createTransportMessage(Message $message);

    /**
     * @param TransportMessage $message
     *
     * @return Message
     */
    public function createClientMessage(TransportMessage $message);

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
     * @return Queue
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
