<?php

namespace Enqueue\Bench;

use Enqueue\AmqpLib\AmqpConnectionFactory;

require_once __DIR__.'/../vendor/autoload.php';

class EnqueueAmqpLibBench extends EnqueueBaseAmqpBench
{
    protected function createContext()
    {
        return (new AmqpConnectionFactory(getenv('AMQP_DSN')))->createContext();
    }
}
