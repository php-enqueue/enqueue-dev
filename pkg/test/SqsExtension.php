<?php

namespace Enqueue\Test;

use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use PHPUnit\Framework\SkippedTestError;

trait SqsExtension
{
    private function buildSqsContext(): SqsContext
    {
        if (false == $dsn = getenv('SQS_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new SqsConnectionFactory($dsn))->createContext();
    }
}
