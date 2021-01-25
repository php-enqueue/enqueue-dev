<?php

namespace Enqueue\Sns\Tests;

use Aws\Sns\SnsClient as AwsSnsClient;
use Enqueue\Sns\SnsClient;
use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Sns\SnsContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class SnsConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, SnsConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new SnsConnectionFactory([]);

        $this->assertAttributeEquals([
            'lazy' => true,
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'version' => '2010-03-31',
            'endpoint' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new SnsConnectionFactory(['key' => 'theKey']);

        $this->assertAttributeEquals([
            'lazy' => true,
            'key' => 'theKey',
            'secret' => null,
            'token' => null,
            'region' => null,
            'version' => '2010-03-31',
            'endpoint' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithClient()
    {
        $awsClient = $this->createMock(AwsSnsClient::class);

        $factory = new SnsConnectionFactory($awsClient);

        $context = $factory->createContext();

        $this->assertInstanceOf(SnsContext::class, $context);

        $client = $this->readAttribute($context, 'client');
        $this->assertInstanceOf(SnsClient::class, $client);
        $this->assertAttributeSame($awsClient, 'inputClient', $client);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new SnsConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(SnsContext::class, $context);

        $client = $this->readAttribute($context, 'client');
        $this->assertInstanceOf(SnsClient::class, $client);
        $this->assertAttributeInstanceOf(\Closure::class, 'inputClient', $client);
    }
}
