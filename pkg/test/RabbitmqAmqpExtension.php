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
        if (false == getenv('SYMFONY__RABBITMQ__HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('SYMFONY__RABBITMQ__HOST'),
            'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
            'user' => getenv('SYMFONY__RABBITMQ__USER'),
            'pass' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
            'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
        ];

        return (new AmqpConnectionFactory($config))->createContext();
    }
}
