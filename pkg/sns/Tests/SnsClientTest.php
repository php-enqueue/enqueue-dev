<?php

namespace Enqueue\Sns\Tests;

use AsyncAws\Core\Result;
use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\Sns\Result\CreateTopicResponse;
use AsyncAws\Sns\Result\PublishResponse;
use AsyncAws\Sns\SnsClient as AwsSnsClient;
use Enqueue\Sns\SnsClient;
use PHPUnit\Framework\TestCase;

class SnsClientTest extends TestCase
{
    public function testShouldAllowGetAwsClientIfSingleClientProvided()
    {
        $awsClient = new AwsSnsClient([
            'endpoint' => 'http://localhost',
        ]);

        $client = new SnsClient($awsClient);

        $this->assertSame($awsClient, $client->getAWSClient());
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $expectedResult = ResultMockFactory::create(...$result);
        $awsClient
            ->expects($this->once())
            ->method($method)
            ->with($this->identicalTo($args))
            ->willReturn($expectedResult);

        $client = new SnsClient($awsClient);

        $actualResult = $client->{$method}($args);

        if ($actualResult !== null || !$expectedResult instanceof Result) {
            $this->assertInstanceOf(Result::class, $actualResult);
            $this->assertSame($expectedResult, $actualResult);
        }
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testLazyApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $expectedResult = ResultMockFactory::create(...$result);
        $awsClient
            ->expects($this->once())
            ->method($method)
            ->with($this->identicalTo($args))
            ->willReturn($expectedResult);

        $client = new SnsClient(function () use ($awsClient) {
            return $awsClient;
        });

        $actualResult = $client->{$method}($args);

        if ($actualResult !== null || !$expectedResult instanceof Result) {
            $this->assertInstanceOf(Result::class, $actualResult);
            $this->assertSame($expectedResult, $actualResult);
        }
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testThrowIfInvalidInputClientApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $client = new SnsClient(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "AsyncAws\Sns\SnsClient" or a callable that returns it. Got "stdClass"');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testThrowIfInvalidLazyInputClientApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $client = new SnsClient(function () { return new \stdClass(); });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "AsyncAws\Sns\SnsClient" or a callable that returns it. Got "stdClass"');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testApiCallWithMultiClientAndCustomRegion(string $method, array $args, array $result, string $awsClientClass)
    {
        $args['@region'] = 'theRegion';

        $awsClient = $this->getMockBuilder($awsClientClass)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock();
        $expectedResult = ResultMockFactory::create(...$result);
        $awsClient
            ->expects($this->once())
            ->method($method)
            ->with($this->identicalTo($args))
            ->willReturn($expectedResult);

        $client = new SnsClient($awsClient);

        $actualResult = $client->{$method}($args);

        if ($actualResult !== null || !$expectedResult instanceof Result) {
            $this->assertInstanceOf(Result::class, $actualResult);
            $this->assertSame($expectedResult, $actualResult);
        }
    }

    public function provideApiCallsSingleClient()
    {
        yield [
            'createTopic',
            ['fooArg' => 'fooArgVal'],
            [CreateTopicResponse::class, []],
            AwsSnsClient::class,
        ];

        yield [
            'publish',
            ['fooArg' => 'fooArgVal'],
            [PublishResponse::class, []],
            AwsSnsClient::class,
        ];
    }
}
