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
        if (false == getenv('AWS__SQS__KEY')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'key' => getenv('AWS__SQS__KEY'),
            'secret' => getenv('AWS__SQS__SECRET'),
            'region' => getenv('AWS__SQS__REGION'),
            'lazy' => false,
        ];

        return (new SqsConnectionFactory($config))->createContext();
    }
}
