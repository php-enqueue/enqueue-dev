<?php

namespace Enqueue\Null;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;

class NullContext implements PsrContext
{
    /**
     * {@inheritdoc}
     *
     * @return NullMessage
     */
    public function createMessage($body = null, array $properties = [], array $headers = [])
    {
        $message = new NullMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @return NullQueue
     */
    public function createQueue($name)
    {
        return new NullQueue($name);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryQueue()
    {
        return $this->createQueue(uniqid('', true));
    }

    /**
     * {@inheritdoc}
     *
     * @return NullTopic
     */
    public function createTopic($name)
    {
        return new NullTopic($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return NullConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        return new NullConsumer($destination);
    }

    /**
     * {@inheritdoc}
     */
    public function createProducer()
    {
        return new NullProducer();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }
}
