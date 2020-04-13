<?php

namespace Enqueue\Sns\Tests;

use AsyncAws\Sns\SnsClient as AwsSnsClient;
use Enqueue\Sns\SnsAsyncClient;
use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Sns\SnsContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class SnsConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, SnsConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new SnsConnectionFactory([]);

        $this->assertAttributeEquals([
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'endpoint' => null,
            'profile' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new SnsConnectionFactory(['key' => 'theKey']);

        $this->assertAttributeEquals([
            'key' => 'theKey',
            'secret' => null,
            'token' => null,
            'region' => null,
            'endpoint' => null,
            'profile' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithClient()
    {
        $awsClient = $this->createMock(AwsSnsClient::class);

        $factory = new SnsConnectionFactory($awsClient);

        $context = $factory->createContext();

        $this->assertInstanceOf(SnsContext::class, $context);

        $client = $this->readAttribute($context, 'client');
        $this->assertInstanceOf(SnsAsyncClient::class, $client);
        $this->assertAttributeSame($awsClient, 'client', $client);
    }
}
