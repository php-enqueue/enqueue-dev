<?php

namespace Enqueue\Sqs\Tests;

use Aws\MultiRegionClient;
use Aws\Result;
use Aws\Sdk;
use Aws\Sqs\SqsClient as AwsSqsClient;
use Enqueue\Sqs\SqsClient;
use PHPUnit\Framework\TestCase;

class SqsClientTest extends TestCase
{
    public function testShouldAllowGetAwsClientIfSingleClientProvided()
    {
        $awsClient = (new Sdk(['Sqs' => [
            'key' => '',
            'secret' => '',
            'region' => '',
            'version' => '2012-11-05',
            'endpoint' => 'http://localhost',
        ]]))->createSqs();

        $client = new SqsClient($awsClient);

        $this->assertSame($awsClient, $client->getAWSClient());
    }

    public function testShouldAllowGetAwsClientIfMultipleClientProvided()
    {
        $awsClient = (new Sdk(['Sqs' => [
            'key' => '',
            'secret' => '',
            'region' => '',
            'version' => '2012-11-05',
            'endpoint' => 'http://localhost',
        ]]))->createMultiRegionSqs();

        $client = new SqsClient($awsClient);

        $this->assertInstanceOf(AwsSqsClient::class, $client->getAWSClient());
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     * @dataProvider provideApiCallsMultipleClient
     */
    public function testApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $awsClient
            ->expects($this->once())
            ->method($method)
            ->with($this->identicalTo($args))
            ->willReturn(new Result($result));

        $client = new SqsClient($awsClient);

        $actualResult = $client->{$method}($args);

        $this->assertInstanceOf(Result::class, $actualResult);
        $this->assertSame($result, $actualResult->toArray());
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     * @dataProvider provideApiCallsMultipleClient
     */
    public function testLazyApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $awsClient
            ->expects($this->once())
            ->method($method)
            ->with($this->identicalTo($args))
            ->willReturn(new Result($result));

        $client = new SqsClient(function () use ($awsClient) {
            return $awsClient;
        });

        $actualResult = $client->{$method}($args);

        $this->assertInstanceOf(Result::class, $actualResult);
        $this->assertSame($result, $actualResult->toArray());
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     * @dataProvider provideApiCallsMultipleClient
     */
    public function testThrowIfInvalidInputClientApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $client = new SqsClient(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "Aws\Sqs\SqsClient" or "Aws\MultiRegionClient" or a callable that returns one of those. Got "stdClass"');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     * @dataProvider provideApiCallsMultipleClient
     */
    public function testThrowIfInvalidLazyInputClientApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $client = new SqsClient(function () { return new \stdClass(); });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "Aws\Sqs\SqsClient" or "Aws\MultiRegionClient" or a callable that returns one of those. Got "stdClass"');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsMultipleClient
     */
    public function testApiCallWithMultiClientAndCustomRegion(string $method, array $args, array $result, string $awsClientClass)
    {
        $args['@region'] = 'theRegion';

        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $awsClient
            ->expects($this->once())
            ->method($method)
            ->with($this->identicalTo($args))
            ->willReturn(new Result($result));

        $client = new SqsClient($awsClient);

        $actualResult = $client->{$method}($args);

        $this->assertInstanceOf(Result::class, $actualResult);
        $this->assertSame($result, $actualResult->toArray());
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testApiCallWithSingleClientAndCustomRegion(string $method, array $args, array $result, string $awsClientClass)
    {
        $args['@region'] = 'theRegion';

        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $awsClient
            ->expects($this->never())
            ->method($method)
        ;

        $client = new SqsClient($awsClient);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot send message to another region because transport is configured with single aws client');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testApiCallWithMultiClientAndEmptyCustomRegion(string $method, array $args, array $result, string $awsClientClass)
    {
        $expectedArgs = $args;
        $args['@region'] = '';

        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $awsClient
            ->expects($this->once())
            ->method($method)
            ->with($this->identicalTo($expectedArgs))
            ->willReturn(new Result($result));

        $client = new SqsClient($awsClient);

        $actualResult = $client->{$method}($args);

        $this->assertInstanceOf(Result::class, $actualResult);
        $this->assertSame($result, $actualResult->toArray());
    }

    public function provideApiCallsSingleClient()
    {
        yield [
            'deleteMessage',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];

        yield [
            'receiveMessage',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];

        yield [
            'purgeQueue',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];

        yield [
            'getQueueUrl',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];

        yield [
            'getQueueAttributes',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];

        yield [
            'createQueue',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];

        yield [
            'deleteQueue',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];

        yield [
            'sendMessage',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSqsClient::class,
        ];
    }

    public function provideApiCallsMultipleClient()
    {
        yield [
            'deleteMessage',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'receiveMessage',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'purgeQueue',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'getQueueUrl',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'getQueueAttributes',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'createQueue',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'deleteQueue',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'sendMessage',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];
    }
}
