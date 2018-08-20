<?php

namespace Enqueue;

use Enqueue\Consumption\QueueConsumer;
use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;

/**
 * @param string|array $config
 */
function dsn_to_connection_factory($config): PsrConnectionFactory
{
    return (new ConnectionFactoryFactory())->create($config);
}

/**
 * @param string $dsn
 *
 * @return PsrContext
 */
function dsn_to_context($dsn)
{
    return dsn_to_connection_factory($dsn)->createContext();
}

/**
 * @param PsrContext $c
 * @param string     $topic
 * @param string     $body
 */
function send_topic(PsrContext $c, $topic, $body)
{
    $topic = $c->createTopic($topic);
    $message = $c->createMessage($body);

    $c->createProducer()->send($topic, $message);
}

/**
 * @param PsrContext $c
 * @param string     $queue
 * @param string     $body
 */
function send_queue(PsrContext $c, $queue, $body)
{
    $queue = $c->createQueue($queue);
    $message = $c->createMessage($body);

    $c->createProducer()->send($queue, $message);
}

/**
 * @param PsrContext $c
 * @param string     $queueName
 * @param callable   $callback
 */
function consume(PsrContext $c, string $queueName, callable $callback)
{
    $queueConsumer = new QueueConsumer($c);
    $queueConsumer->bindCallback($queueName, $callback);

    $queueConsumer->consume();
}
