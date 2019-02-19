<?php

namespace Enqueue\Test;

use Enqueue\SnsQs\SnsQsConnectionFactory;
use Enqueue\SnsQs\SnsQsContext;

trait SnsQsExtension
{
    private function buildSnsQsContext(): SnsQsContext
    {
        $snsDsn = getenv('SNS_DSN');
        $sqsDsn = getenv('SQS_DSN');

        if (false == $snsDsn || false == $sqsDsn) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new SnsQsConnectionFactory(['sns' => $snsDsn, 'sqs' => $sqsDsn]))->createContext();
    }
}
