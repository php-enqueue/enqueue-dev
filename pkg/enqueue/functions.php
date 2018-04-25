<?php

namespace Enqueue;

use Enqueue\AmqpBunny\AmqpConnectionFactory as AmqpBunnyConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Stomp\StompConnectionFactory;
use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;

/**
 * @param string $dsn
 *
 * @return PsrConnectionFactory
 */
function dsn_to_connection_factory($dsn)
{
    $map = [];

    if (class_exists(FsConnectionFactory::class)) {
        $map['file'] = FsConnectionFactory::class;
    }

    if (class_exists(AmqpExtConnectionFactory::class)) {
        $map['amqp+ext'] = AmqpExtConnectionFactory::class;
        $map['amqps+ext'] = AmqpExtConnectionFactory::class;
    }
    if (class_exists(AmqpLibConnectionFactory::class)) {
        $map['amqp+lib'] = AmqpLibConnectionFactory::class;
        $map['amqps+lib'] = AmqpLibConnectionFactory::class;
    }
    if (class_exists(AmqpBunnyConnectionFactory::class)) {
        $map['amqp+bunny'] = AmqpBunnyConnectionFactory::class;
    }

    if (class_exists(AmqpExtConnectionFactory::class)) {
        $map['amqp'] = AmqpExtConnectionFactory::class;
    } elseif (class_exists(AmqpBunnyConnectionFactory::class)) {
        $map['amqp'] = AmqpBunnyConnectionFactory::class;
    } elseif (class_exists(AmqpLibConnectionFactory::class)) {
        $map['amqp'] = AmqpLibConnectionFactory::class;
    }

    if (class_exists(AmqpExtConnectionFactory::class)) {
        $map['amqps'] = AmqpExtConnectionFactory::class;
    } elseif (class_exists(AmqpLibConnectionFactory::class)) {
        $map['amqps'] = AmqpLibConnectionFactory::class;
    }

    if (class_exists(NullConnectionFactory::class)) {
        $map['null'] = NullConnectionFactory::class;
    }

    if (class_exists(DbalConnectionFactory::class)) {
        $map['db2'] = DbalConnectionFactory::class;
        $map['ibm_db2'] = DbalConnectionFactory::class;
        $map['mssql'] = DbalConnectionFactory::class;
        $map['pdo_sqlsrv'] = DbalConnectionFactory::class;
        $map['mysql'] = DbalConnectionFactory::class;
        $map['mysql2'] = DbalConnectionFactory::class;
        $map['pdo_mysql'] = DbalConnectionFactory::class;
        $map['pgsql'] = DbalConnectionFactory::class;
        $map['postgres'] = DbalConnectionFactory::class;
        $map['postgresql'] = DbalConnectionFactory::class;
        $map['pdo_pgsql'] = DbalConnectionFactory::class;
        $map['sqlite'] = DbalConnectionFactory::class;
        $map['sqlite3'] = DbalConnectionFactory::class;
        $map['pdo_sqlite'] = DbalConnectionFactory::class;
    }

    if (class_exists(GearmanConnectionFactory::class)) {
        $map['gearman'] = GearmanConnectionFactory::class;
    }

    if (class_exists(PheanstalkConnectionFactory::class)) {
        $map['beanstalk'] = PheanstalkConnectionFactory::class;
    }

    if (class_exists(RdKafkaConnectionFactory::class)) {
        $map['kafka'] = RdKafkaConnectionFactory::class;
        $map['rdkafka'] = RdKafkaConnectionFactory::class;
    }

    if (class_exists(RedisConnectionFactory::class)) {
        $map['redis'] = RedisConnectionFactory::class;
    }

    if (class_exists(StompConnectionFactory::class)) {
        $map['stomp'] = StompConnectionFactory::class;
    }

    if (class_exists(SqsConnectionFactory::class)) {
        $map['sqs'] = SqsConnectionFactory::class;
    }

    if (class_exists(GpsConnectionFactory::class)) {
        $map['gps'] = GpsConnectionFactory::class;
    }

    list($scheme) = explode(':', $dsn, 2);
    if (false == $scheme || false === strpos($dsn, ':')) {
        throw new \LogicException(sprintf('The scheme could not be parsed from DSN "%s"', $dsn));
    }

    if (false == array_key_exists($scheme, $map)) {
        throw new \LogicException(sprintf(
            'The scheme "%s" is not supported. Supported "%s"',
            $scheme,
            implode('", "', array_keys($map))
        ));
    }

    $factoryClass = $map[$scheme];

    return new $factoryClass($dsn);
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
 * @param string     $queue
 * @param callable   $callback
 */
function consume(PsrContext $c, $queue, callable $callback)
{
    $queueConsumer = new QueueConsumer($c);
    $queueConsumer->bind($queue, $callback);

    $queueConsumer->consume();
}
