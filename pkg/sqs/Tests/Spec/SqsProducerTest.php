<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsConnectionFactory;
use Interop\Queue\Spec\PsrProducerSpec;

/**
 * @group functional
 */
class SqsProducerTest extends PsrProducerSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        $factory = new SqsConnectionFactory([
            'key' => getenv('AWS__SQS__KEY'),
            'secret' => getenv('AWS__SQS__SECRET'),
            'region' => getenv('AWS__SQS__REGION'),
        ]);

        return $factory->createContext()->createProducer();
    }
}
