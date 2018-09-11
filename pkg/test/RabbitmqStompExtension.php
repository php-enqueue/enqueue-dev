<?php

namespace Enqueue\Test;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;

trait RabbitmqStompExtension
{
    private function buildStompContext(): StompContext
    {
        if (false == $dsn = getenv('RABITMQ_STOMP_DSN')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new StompConnectionFactory($dsn))->createContext();
    }
}
