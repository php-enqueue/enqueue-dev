<?php

namespace Enqueue\Sqs\Tests;

use Aws\Sqs\SqsClient;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;

class SqsConnectionFactoryTest extends \PHPUnit\Framework\TestCase
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
            'lazy' => true,
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'retries' => 3,
            'version' => '2012-11-05',
            'endpoint' => null,
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
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithClient()
    {
        $client = $this->createMock(SqsClient::class);

        $factory = new SqsConnectionFactory($client);

        $context = $factory->createContext();

        $this->assertInstanceOf(SqsContext::class, $context);
        $this->assertAttributeSame($client, 'client', $context);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new SqsConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(SqsContext::class, $context);

        $this->assertAttributeEquals(null, 'client', $context);
        $this->assertInternalType('callable', $this->readAttribute($context, 'clientFactory'));
    }
}
