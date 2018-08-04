<?php

namespace Enqueue\Sqs\Tests;

use Aws\Result;
use Aws\Sqs\SqsClient;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrProducer;

class SqsProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, SqsProducer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SqsProducer($this->createSqsContextMock());
    }

    public function testShouldThrowIfBodyOfInvalidType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message body must be a non-empty string. Got: stdClass');

        $producer = new SqsProducer($this->createSqsContextMock());

        $message = new SqsMessage(new \stdClass());

        $producer->send(new SqsDestination(''), $message);
    }

    public function testShouldThrowIfDestinationOfInvalidType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Sqs\SqsDestination but got Mock_PsrDestinat');

        $producer = new SqsProducer($this->createSqsContextMock());

        $producer->send($this->createMock(PsrDestination::class), new SqsMessage());
    }

    public function testShouldThrowIfSendMessageFailed()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('sendMessage')
            ->willReturn(new Result())
        ;

        $context = $this->createSqsContextMock();
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client))
        ;

        $destination = new SqsDestination('queue-name');
        $message = new SqsMessage('foo');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message was not sent');

        $producer = new SqsProducer($context);
        $producer->send($destination, $message);
    }

    public function testShouldSendMessage()
    {
        $expectedArguments = [
            'MessageAttributes' => [
                'Headers' => [
                    'DataType' => 'String',
                    'StringValue' => '[{"hkey":"hvaleu"},{"key":"value"}]',
                ],
            ],
            'MessageBody' => 'theBody',
            'QueueUrl' => 'theQueueUrl',
            'DelaySeconds' => 12345,
            'MessageDeduplicationId' => 'theDeduplicationId',
            'MessageGroupId' => 'groupId',
        ];

        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('sendMessage')
            ->with($this->identicalTo($expectedArguments))
            ->willReturn(new Result())
        ;

        $context = $this->createSqsContextMock();
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client))
        ;

        $destination = new SqsDestination('queue-name');
        $message = new SqsMessage('theBody', ['key' => 'value'], ['hkey' => 'hvaleu']);
        $message->setDelaySeconds(12345);
        $message->setMessageDeduplicationId('theDeduplicationId');
        $message->setMessageGroupId('groupId');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message was not sent');

        $producer = new SqsProducer($context);
        $producer->send($destination, $message);
    }

    public function testShouldSendDelayedMessage()
    {
        $expectedArguments = [
            'MessageAttributes' => [
                'Headers' => [
                    'DataType' => 'String',
                    'StringValue' => '[{"hkey":"hvaleu"},{"key":"value"}]',
                ],
            ],
            'MessageBody' => 'theBody',
            'QueueUrl' => 'theQueueUrl',
            'DelaySeconds' => 12345,
            'MessageDeduplicationId' => 'theDeduplicationId',
            'MessageGroupId' => 'groupId',
        ];

        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('sendMessage')
            ->with($this->identicalTo($expectedArguments))
            ->willReturn(new Result())
        ;

        $context = $this->createSqsContextMock();
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client))
        ;

        $destination = new SqsDestination('queue-name');
        $message = new SqsMessage('theBody', ['key' => 'value'], ['hkey' => 'hvaleu']);
        $message->setDelaySeconds(12345);
        $message->setMessageDeduplicationId('theDeduplicationId');
        $message->setMessageGroupId('groupId');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message was not sent');

        $producer = new SqsProducer($context);
        $producer->setDeliveryDelay(5000);
        $producer->send($destination, $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsContext
     */
    private function createSqsContextMock()
    {
        return $this->createMock(SqsContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsClient
     */
    private function createSqsClientMock()
    {
        return $this
            ->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendMessage'])
            ->getMock()
        ;
    }
}
