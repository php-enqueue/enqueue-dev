<?php

namespace Enqueue\Test;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;

trait RabbitmqStompExtension
{
    /**
     * @return StompContext
     */
    private function buildStompContext()
    {
        if (false == getenv('RABBITMQ_HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('RABBITMQ_HOST'),
            'port' => getenv('﻿RABBITMQ_STOMP_PORT'),
            'login' => getenv('RABBITMQ_USER'),
            'password' => getenv('RABBITMQ_PASSWORD'),
            'vhost' => getenv('RABBITMQ_VHOST'),
            'sync' => true,
        ];

        return (new StompConnectionFactory($config))->createContext();
    }
}
