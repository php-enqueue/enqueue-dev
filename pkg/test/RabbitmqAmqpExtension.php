<?php

namespace Enqueue\Test;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use PHPUnit\Framework\SkippedTestError;

trait RabbitmqAmqpExtension
{
    /**
     * @return AmqpContext
     */
    private function buildAmqpContext()
    {
        if (false == $dsn = getenv('AMQP_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new AmqpConnectionFactory($dsn))->createContext();
    }
}
