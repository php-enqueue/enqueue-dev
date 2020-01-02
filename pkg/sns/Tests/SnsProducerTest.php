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
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Exception\TimeToLiveNotSupportedException;
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
            ->willReturn($client)
        ;

        $message = new SnsMessage('foo');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message was not sent');

        $producer = new SnsProducer($context);
        $producer->send($destination, $message);
    }

    public function testShouldThrowIfsetTimeToLiveIsNotNull()
    {
        $this->expectException(TimeToLiveNotSupportedException::class);

        $producer = new SnsProducer($this->createSnsContextMock());
        $result = $producer->setTimeToLive();

        $this->assertInstanceOf(SnsProducer::class, $result);

        $this->expectExceptionMessage('The provider does not support time to live feature');

        $producer->setTimeToLive(200);
    }

    public function testShouldThrowIfsetPriorityIsNotNull()
    {
        $this->expectException(PriorityNotSupportedException::class);

        $producer = new SnsProducer($this->createSnsContextMock());
        $result = $producer->setPriority();

        $this->assertInstanceOf(SnsProducer::class, $result);

        $this->expectExceptionMessage('The provider does not support priority feature');

        $producer->setPriority(200);
    }

    public function testShouldThrowIfsetDeliveryDelayIsNotNull()
    {
        $this->expectException(DeliveryDelayNotSupportedException::class);

        $producer = new SnsProducer($this->createSnsContextMock());
        $result = $producer->setDeliveryDelay();

        $this->assertInstanceOf(SnsProducer::class, $result);

        $this->expectExceptionMessage('The provider does not support delivery delay feature');

        $producer->setDeliveryDelay(200);
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
            ->willReturn($client)
        ;

        $message = new SnsMessage('theBody', ['key' => 'value'], ['hkey' => 'hvaleu']);

        $producer = new SnsProducer($context);
        $producer->send($destination, $message);
    }

    /**
     * @throws InvalidMessageException
     */
    public function testShouldPublishWithMergedAttributes()
    {
        $context = $this->createSnsContextMock();
        $client = $this->createSnsClientMock();

        $context
            ->expects($this->once())
            ->method('getSnsClient')
            ->willReturn($client);

        $expectedArgument = [
            'Message' => 'message',
            'MessageAttributes' => [
                'Headers' => [
                    'DataType' => 'String',
                    'StringValue' => '[[],[]]',
                ],
                'Foo' => [
                    'DataType' => 'String',
                    'StringValue' => 'foo-value',
                ],
                'Bar' => [
                    'DataType' => 'Binary',
                    'BinaryValue' => 'bar-val',
                ],
            ],
            'TopicArn' => '',
            'MessageStructure' => 'structure',
            'PhoneNumber' => 'phone',
            'Subject' => 'subject',
            'TargetArn' => 'target_arn',
        ];

        $client
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($expectedArgument))
            ->willReturn(new Result(['MessageId' => 'theMessageId']));

        $attributes = [
            'Foo' => [
                'DataType' => 'String',
                'StringValue' => 'foo-value',
            ],
        ];

        $message = new SnsMessage(
            'message', [], [], $attributes, 'structure', 'phone',
            'subject', 'target_arn'
        );
        $message->addAttribute('Bar', 'Binary', 'bar-val');

        $destination = new SnsDestination('queue-name');

        $producer = new SnsProducer($context);
        $producer->send($destination, $message);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SnsContext
     */
    private function createSnsContextMock(): SnsContext
    {
        return $this->createMock(SnsContext::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SnsClient
     */
    private function createSnsClientMock(): SnsClient
    {
        return $this->createMock(SnsClient::class);
    }
}
