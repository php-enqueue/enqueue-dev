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
        if (false == getenv('SYMFONY__RABBITMQ__HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('SYMFONY__RABBITMQ__HOST'),
            'port' => getenv('SYMFONY__RABBITMQ__STOMP__PORT'),
            'login' => getenv('SYMFONY__RABBITMQ__USER'),
            'password' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
            'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
            'sync' => true,
        ];

        return (new StompConnectionFactory($config))->createContext();
    }
}
