<?php

namespace Enqueue\Sns\Tests;

use AsyncAws\Core\Result;
use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\Sns\Result\CreateTopicResponse;
use AsyncAws\Sns\Result\PublishResponse;
use AsyncAws\Sns\SnsClient as AwsSnsClient;
use Enqueue\Sns\SnsAsyncClient;
use PHPUnit\Framework\TestCase;

class SnsAsyncClientTest extends TestCase
{
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

        $client = new SnsAsyncClient($awsClient);

        $actualResult = $client->{$method}($args);

        if ($actualResult !== null || !$expectedResult instanceof Result) {
            $this->assertInstanceOf(Result::class, $actualResult);
            $this->assertSame($expectedResult, $actualResult);
        }
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

        $client = new SnsAsyncClient($awsClient);

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
