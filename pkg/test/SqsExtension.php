<?php

namespace Enqueue\Test;

use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;

trait SqsExtension
{
    /**
     * @return SqsContext
     */
    private function buildSqsContext()
    {
        if (false == getenv('AWS_SQS_KEY')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'key' => getenv('AWS_SQS_KEY'),
            'secret' => getenv('AWS_SQS_SECRET'),
            'region' => getenv('AWS_SQS_REGION'),
            'lazy' => false,
        ];

        return (new SqsConnectionFactory($config))->createContext();
    }
}
