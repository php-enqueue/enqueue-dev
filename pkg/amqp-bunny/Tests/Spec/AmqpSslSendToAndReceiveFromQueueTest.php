<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Enqueue\AmqpBunny\AmqpContext;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class AmqpSslSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
{
    public function test()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The bunny library does not support SSL connections');
        parent::test();
    }

    protected function createContext()
    {
        $baseDir = realpath(__DIR__.'/../../../../');

        // guard
        $this->assertNotEmpty($baseDir);

        $certDir = $baseDir.'/var/rabbitmq_certificates';
        $this->assertDirectoryExists($certDir);

        $factory = new AmqpConnectionFactory([
            'dsn' => getenv('AMQPS_DSN'),
            'ssl_verify' => false,
            'ssl_cacert' => $certDir.'/cacert.pem',
            'ssl_cert' => $certDir.'/cert.pem',
            'ssl_key' => $certDir.'/key.pem',
        ]);

        return $factory->createContext();
    }

    /**
     * @param AmqpContext $context
     */
    protected function createQueue(Context $context, $queueName)
    {
        $queue = $context->createQueue($queueName);
        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        return $queue;
    }
}
