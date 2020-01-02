<?php

namespace Enqueue\Test;

use Enqueue\SnsQs\SnsQsConnectionFactory;
use Enqueue\SnsQs\SnsQsContext;
use PHPUnit\Framework\SkippedTestError;

trait SnsQsExtension
{
    private function buildSnsQsContext(): SnsQsContext
    {
        if (false == $dsn = getenv('SNSQS_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new SnsQsConnectionFactory($dsn))->createContext();
    }
}
