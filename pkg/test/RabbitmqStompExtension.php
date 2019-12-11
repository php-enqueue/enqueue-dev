<?php

namespace Enqueue\Test;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use PHPUnit\Framework\SkippedTestError;

trait RabbitmqStompExtension
{
    private function buildStompContext(): StompContext
    {
        if (false == $dsn = getenv('RABITMQ_STOMP_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new StompConnectionFactory($dsn))->createContext();
    }
}
