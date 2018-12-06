<?php

namespace Enqueue\Sns\Tests;

use Aws\Result;
use Enqueue\Sns\SnsClient;
use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsDestination;
use Enqueue\Sns\SnsMessage;
use Enqueue\Sns\SnsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Producer;
use PHPUnit\Framework\TestCase;

class SnsProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, SnsProducer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SnsProducer($this->createSnsContextMock());
    }

    public function testShouldThrowIfBodyOfInvalidType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message body must be a non-empty string.');

        $producer = new SnsProducer($this->createSnsContextMock());

        $message = new SnsMessage('');

        $producer->send(new SnsDestination(''), $message);
    }

    public function testShouldThrowIfDestinationOfInvalidType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Sns\SnsDestination but got Mock_Destinat');

        $producer = new SnsProducer($this->createSnsContextMock());

        $producer->send($this->createMock(Destination::class), new SnsMessage());
    }

    public function testShouldThrowIfPublishFailed()
    {
        $destination = new SnsDestination('queue-name');

        $client = $this->createSnsClientMock();
        $client
            ->expects($this->once())
            ->method('publish')
            ->willReturn(new Result())
        ;

        $context = $this->createSnsContextMock();
        $context
            ->expects($this->once())
            ->method('getTopicArn')
            ->with($this->identicalTo($destination))
            ->willReturn('theTopicArn')
        ;
        $context
            ->expects($this->once())
            ->method('getSnsClient')
            ->will($this->returnValue($client))
        ;

        $message = new SnsMessage('foo');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message was not sent');

        $producer = new SnsProducer($context);
        $producer->send($destination, $message);
    }

    public function testShouldPublish()
    {
        $destination = new SnsDestination('queue-name');

        $expectedArguments = [
            'Message' => 'theBody',
            'MessageAttributes' => [
                'Headers' => [
                    'DataType' => 'String',
                    'StringValue' => '[{"hkey":"hvaleu"},{"key":"value"}]',
                ],
            ],
            'TopicArn' => 'theTopicArn',
        ];

        $client = $this->createSnsClientMock();
        $client
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($expectedArguments))
            ->willReturn(new Result(['MessageId' => 'theMessageId']))
        ;

        $context = $this->createSnsContextMock();
        $context
            ->expects($this->once())
            ->method('getTopicArn')
            ->with($this->identicalTo($destination))
            ->willReturn('theTopicArn')
        ;
        $context
            ->expects($this->once())
            ->method('getSnsClient')
            ->will($this->returnValue($client))
        ;

        $message = new SnsMessage('theBody', ['key' => 'value'], ['hkey' => 'hvaleu']);

        $producer = new SnsProducer($context);
        $producer->send($destination, $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SnsContext
     */
    private function createSnsContextMock(): SnsContext
    {
        return $this->createMock(SnsContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SnsClient
     */
    private function createSnsClientMock(): SnsClient
    {
        return $this->createMock(SnsClient::class);
    }
}
