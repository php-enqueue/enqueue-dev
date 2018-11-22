<?php

namespace Enqueue\Test;

use Enqueue\Wamp\WampConnectionFactory;
use Enqueue\Wamp\WampContext;

trait WampExtension
{
    private function buildWampContext(): WampContext
    {
        if (false == $dsn = getenv('WAMP_DSN')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new WampConnectionFactory($dsn))->createContext();
    }
}
