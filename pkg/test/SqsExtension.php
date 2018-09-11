<?php

namespace Enqueue\Test;

use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;

trait SqsExtension
{
    private function buildSqsContext(): SqsContext
    {
        if (false == $dsn = getenv('SQS_DSN')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new SqsConnectionFactory($dsn))->createContext();
    }
}
