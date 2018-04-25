<?php

namespace Enqueue\Test;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;

trait RabbitmqAmqpExtension
{
    /**
     * @return AmqpContext
     */
    private function buildAmqpContext()
    {
        if (false == getenv('RABBITMQ_HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('RABBITMQ_HOST'),
            'port' => getenv('RABBITMQ_AMQP__PORT'),
            'user' => getenv('RABBITMQ_USER'),
            'pass' => getenv('RABBITMQ_PASSWORD'),
            'vhost' => getenv('RABBITMQ_VHOST'),
        ];

        return (new AmqpConnectionFactory($config))->createContext();
    }

    /**
     * @return AmqpContext
     */
    private function buildAmqpContextFromDsn()
    {
        if (false == $dsn = getenv('AMQP_DSN')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new AmqpConnectionFactory($dsn))->createContext();
    }
}
