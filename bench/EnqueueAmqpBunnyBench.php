<?php

namespace Enqueue\Bench;

use Enqueue\AmqpBunny\AmqpConnectionFactory;

require_once __DIR__.'/../vendor/autoload.php';

class EnqueueAmqpBunnyBench extends EnqueueBaseAmqpBench
{
    protected function createContext()
    {
        return (new AmqpConnectionFactory(getenv('AMQP_DSN')))->createContext();
    }
}
