<?php

namespace Enqueue\Sqs\Tests;

use AsyncAws\Sqs\SqsClient;
use AsyncAws\Sqs\SqsClient as AwsSqsClient;
use Enqueue\Sqs\SqsAsyncClient;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class SqsConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, SqsConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new SqsConnectionFactory([]);

        $this->assertAttributeEquals([
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'endpoint' => null,
            'profile' => null,
            'queue_owner_aws_account_id' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new SqsConnectionFactory(['key' => 'theKey']);

        $this->assertAttributeEquals([
            'key' => 'theKey',
            'secret' => null,
            'token' => null,
            'region' => null,
            'endpoint' => null,
            'profile' => null,
            'queue_owner_aws_account_id' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithClient()
    {
        $awsClient = $this->createMock(AwsSqsClient::class);

        $factory = new SqsConnectionFactory($awsClient);

        $context = $factory->createContext();

        $this->assertInstanceOf(SqsContext::class, $context);

        $client = $this->readAttribute($context, 'client');
        $this->assertInstanceOf(SqsAsyncClient::class, $client);
        $this->assertAttributeSame($awsClient, 'client', $client);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new SqsConnectionFactory();

        $context = $factory->createContext();

        $this->assertInstanceOf(SqsContext::class, $context);

        $client = $this->readAttribute($context, 'client');
        $this->assertInstanceOf(SqsAsyncClient::class, $client);
        $this->assertAttributeInstanceOf(SqsClient::class, 'client', $client);
    }
}
