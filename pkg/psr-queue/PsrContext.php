<?php

namespace Enqueue\Psr;

interface PsrContext
{
    /**
     * @param string $body
     * @param array  $properties
     * @param array  $headers
     *
     * @return PsrMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = []);

    /**
     * @param string $topicName
     *
     * @return PsrTopic
     */
    public function createTopic($topicName);

    /**
     * @param string $queueName
     *
     * @return PsrQueue
     */
    public function createQueue($queueName);

    /**
     * Create temporary queue.
     * The queue is visible by this connection only.
     * It will be deleted once the connection is closed.
     *
     * @return PsrQueue
     */
    public function createTemporaryQueue();

    /**
     * @return PsrProducer
     */
    public function createProducer();

    /**
     * @param PsrDestination $destination
     *
     * @return PsrConsumer
     */
    public function createConsumer(PsrDestination $destination);

    public function close();
}
