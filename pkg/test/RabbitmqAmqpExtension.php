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
        if (false == $dsn = getenv('AMQP_DSN')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new AmqpConnectionFactory($dsn))->createContext();
    }
}
