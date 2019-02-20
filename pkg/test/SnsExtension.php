<?php

namespace Enqueue\Test;

use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Sns\SnsContext;

trait SnsExtension
{
    private function buildSqsContext(): SnsContext
    {
        if (false == $dsn = getenv('SNS_DSN')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new SnsConnectionFactory($dsn))->createContext();
    }
}
