<?php

namespace Enqueue\Sqs\Tests;

use AsyncAws\Core\Result;
use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\Sqs\Result\CreateQueueResult;
use AsyncAws\Sqs\Result\GetQueueAttributesResult;
use AsyncAws\Sqs\Result\GetQueueUrlResult;
use AsyncAws\Sqs\Result\ReceiveMessageResult;
use AsyncAws\Sqs\Result\SendMessageResult;
use AsyncAws\Sqs\SqsClient as AwsSqsClient;
use Enqueue\Sqs\SqsClient;
use PHPUnit\Framework\TestCase;

class SqsClientTest extends TestCase
{
    public function testShouldAllowGetAwsClientIfSingleClientProvided()
    {
        $awsClient = new AwsSqsClient([
            'endpoint' => 'http://localhost',
        ]);

        $client = new SqsClient($awsClient);

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

        $client = new SqsClient($awsClient);

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

        $client = new SqsClient(function () use ($awsClient) {
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
        $client = new SqsClient(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "AsyncAws\Sqs\SqsClient" or a callable that returns it. Got "stdClass"');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testThrowIfInvalidLazyInputClientApiCall(string $method, array $args, array $result, string $awsClientClass)
    {
        $client = new SqsClient(function () { return new \stdClass(); });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The input client must be an instance of "AsyncAws\Sqs\SqsClient" or a callable that returns it. Got "stdClass"');
        $client->{$method}($args);
    }

    /**
     * @dataProvider provideApiCallsSingleClient
     */
    public function testApiCallWithCustomRegion(string $method, array $args, array $result, string $awsClientClass)
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

        $client = new SqsClient($awsClient);

        $actualResult = $client->{$method}($args);

        if ($actualResult !== null || !$expectedResult instanceof Result) {
            $this->assertInstanceOf(Result::class, $actualResult);
            $this->assertSame($expectedResult, $actualResult);
        }
    }

    public function provideApiCallsSingleClient()
    {
        yield [
            'deleteMessage',
            ['fooArg' => 'fooArgVal'],
            [Result::class, []],
            AwsSqsClient::class,
        ];

        yield [
            'receiveMessage',
            ['fooArg' => 'fooArgVal'],
            [ReceiveMessageResult::class, []],
            AwsSqsClient::class,
        ];

        yield [
            'purgeQueue',
            ['fooArg' => 'fooArgVal'],
            [Result::class, []],
            AwsSqsClient::class,
        ];

        yield [
            'getQueueUrl',
            ['fooArg' => 'fooArgVal'],
            [GetQueueUrlResult::class, []],
            AwsSqsClient::class,
        ];

        yield [
            'getQueueAttributes',
            ['fooArg' => 'fooArgVal'],
            [GetQueueAttributesResult::class, []],
            AwsSqsClient::class,
        ];

        yield [
            'createQueue',
            ['fooArg' => 'fooArgVal'],
            [CreateQueueResult::class, []],
            AwsSqsClient::class,
        ];

        yield [
            'deleteQueue',
            ['fooArg' => 'fooArgVal'],
            [Result::class, []],
            AwsSqsClient::class,
        ];

        yield [
            'sendMessage',
            ['fooArg' => 'fooArgVal'],
            [SendMessageResult::class, []],
            AwsSqsClient::class,
        ];
    }
}
