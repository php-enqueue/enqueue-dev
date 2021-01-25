<?php

namespace Enqueue\Sqs\Tests;

use Aws\Sqs\SqsClient as AwsSqsClient;
use Enqueue\Sqs\SqsClient;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class SqsConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, SqsConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new SqsConnectionFactory([]);

        $this->assertAttributeEquals([
            'lazy' => true,
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'retries' => 3,
            'version' => '2012-11-05',
            'endpoint' => null,
            'profile' => null,
            'queue_owner_aws_account_id' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new SqsConnectionFactory(['key' => 'theKey']);

        $this->assertAttributeEquals([
            'lazy' => true,
            'key' => 'theKey',
            'secret' => null,
            'token' => null,
            'region' => null,
            'retries' => 3,
            'version' => '2012-11-05',
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
        $this->assertInstanceOf(SqsClient::class, $client);
        $this->assertAttributeSame($awsClient, 'inputClient', $client);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new SqsConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(SqsContext::class, $context);

        $client = $this->readAttribute($context, 'client');
        $this->assertInstanceOf(SqsClient::class, $client);
        $this->assertAttributeInstanceOf(\Closure::class, 'inputClient', $client);
    }
}
