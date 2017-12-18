<?php

namespace Enqueue\Bench;

use Enqueue\AmqpExt\AmqpConnectionFactory;

require_once __DIR__.'/../vendor/autoload.php';

class EnqueueAmqpExtBench extends EnqueueBaseAmqpBench
{
    protected function createContext()
    {
        return (new AmqpConnectionFactory(getenv('AMQP_DSN')))->createContext();
    }
}
