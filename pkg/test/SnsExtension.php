<?php

namespace Enqueue\Test;

use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Sns\SnsContext;
use PHPUnit\Framework\SkippedTestError;

trait SnsExtension
{
    private function buildSqsContext(): SnsContext
    {
        if (false == $dsn = getenv('SNS_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new SnsConnectionFactory($dsn))->createContext();
    }
}
