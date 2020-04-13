<?php

namespace Enqueue\Sns\Tests;

use Aws\MultiRegionClient;
use Aws\Result;
use Aws\Sdk;
use Aws\Sns\SnsClient as AwsSnsClient;
use Enqueue\Sns\SnsClient;
use PHPUnit\Framework\TestCase;

class SnsClientTest extends TestCase
{
    public function testShouldAllowGetAwsClientIfSingleClientProvided()
    {
        $awsClient = (new Sdk(['Sns' => [
            'key' => '',
            'secret' => '',
            'token' => '',
            'region' => '',
            'version' => '2010-03-31',
            'endpoint' => 'http://localhost',
        ]]))->createSns();

        $client = new SnsClient($awsClient);

        $this->assertSame($awsClient, $client->getAWSClient());
    }

    public function testShouldAllowGetAwsClientIfMultipleClientProvided()
    {
        $awsClient = (new Sdk(['Sns' => [
            'key' => '',
            'secret' => '',
            'token' => '',
            'region' => '',
            'version' => '2010-03-31',
            'endpoint' => 'http://localhost',
        ]]))->createMultiRegionSns();

        $client = new SnsClient($awsClient);

        $this->assertInstanceOf(AwsSnsClient::class, $client->getAWSClient());
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

        $client = new SnsClient($awsClient);

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

        $client = new SnsClient(function () use ($awsClient) {
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
        $client = new SnsClient(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "Aws\Sns\SnsClient" or "Aws\MultiRegionClient" or a callable that returns one of those. Got "stdClass"');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     * @dataProvider provideApiCallsMultipleClient
     */
    public function testThrowIfInvalidLazyInputClientApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $client = new SnsClient(function () { return new \stdClass(); });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "Aws\Sns\SnsClient" or "Aws\MultiRegionClient" or a callable that returns one of those. Got "stdClass"');
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

        $client = new SnsClient($awsClient);

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

        $client = new SnsClient($awsClient);

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

        $client = new SnsClient($awsClient);

        $actualResult = $client->{$method}($args);

        $this->assertInstanceOf(Result::class, $actualResult);
        $this->assertSame($result, $actualResult->toArray());
    }

    public function provideApiCallsSingleClient()
    {
        yield [
            'createTopic',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSnsClient::class,
        ];

        yield [
            'publish',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            AwsSnsClient::class,
        ];
    }

    public function provideApiCallsMultipleClient()
    {
        yield [
            'createTopic',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];

        yield [
            'publish',
            ['fooArg' => 'fooArgVal'],
            ['bar' => 'barVal'],
            MultiRegionClient::class,
        ];
    }
}
