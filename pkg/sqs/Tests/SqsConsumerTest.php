<?php

namespace Enqueue\Sqs\Tests;

use Aws\Result;
use Enqueue\Sqs\SqsClient;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;

class SqsConsumerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, SqsConsumer::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SqsConsumer($this->createContextMock(), new SqsDestination('queue'));
    }

    public function testShouldReturnInstanceOfDestination()
    {
        $destination = new SqsDestination('queue');

        $consumer = new SqsConsumer($this->createContextMock(), $destination);

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testAcknowledgeShouldThrowIfInstanceOfMessageIsInvalid()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Sqs\SqsMessage but it is Mock_Message');

        $consumer = new SqsConsumer($this->createContextMock(), new SqsDestination('queue'));
        $consumer->acknowledge($this->createMock(Message::class));
    }

    public function testCouldAcknowledgeMessage()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('deleteMessage')
            ->with($this->identicalTo([
                '@region' => null,
                'QueueUrl' => 'theQueueUrl',
                'ReceiptHandle' => 'theReceipt',
            ]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;

        $message = new SqsMessage();
        $message->setReceiptHandle('theReceipt');

        $consumer = new SqsConsumer($context, new SqsDestination('queue'));
        $consumer->acknowledge($message);
    }

    public function testCouldAcknowledgeMessageWithCustomRegion()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('deleteMessage')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueUrl' => 'theQueueUrl',
                'ReceiptHandle' => 'theReceipt',
            ]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;

        $message = new SqsMessage();
        $message->setReceiptHandle('theReceipt');

        $destination = new SqsDestination('queue');
        $destination->setRegion('theRegion');

        $consumer = new SqsConsumer($context, $destination);
        $consumer->acknowledge($message);
    }

    public function testRejectShouldThrowIfInstanceOfMessageIsInvalid()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Sqs\SqsMessage but it is Mock_Message');

        $consumer = new SqsConsumer($this->createContextMock(), new SqsDestination('queue'));
        $consumer->reject($this->createMock(Message::class));
    }

    public function testShouldRejectMessage()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('deleteMessage')
            ->with($this->identicalTo([
                '@region' => null,
                'QueueUrl' => 'theQueueUrl',
                'ReceiptHandle' => 'theReceipt',
            ]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;

        $message = new SqsMessage();
        $message->setReceiptHandle('theReceipt');

        $consumer = new SqsConsumer($context, new SqsDestination('queue'));
        $consumer->reject($message);
    }

    public function testShouldRejectMessageWithCustomRegion()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('deleteMessage')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueUrl' => 'theQueueUrl',
                'ReceiptHandle' => 'theReceipt',
            ]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;

        $message = new SqsMessage();
        $message->setReceiptHandle('theReceipt');

        $destination = new SqsDestination('queue');
        $destination->setRegion('theRegion');

        $consumer = new SqsConsumer($context, $destination);
        $consumer->reject($message);
    }

    public function testShouldRejectMessageAndRequeue()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('changeMessageVisibility')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueUrl' => 'theQueueUrl',
                'ReceiptHandle' => 'theReceipt',
                'VisibilityTimeout' => 0,
            ]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;

        $message = new SqsMessage();
        $message->setReceiptHandle('theReceipt');

        $destination = new SqsDestination('queue');
        $destination->setRegion('theRegion');

        $consumer = new SqsConsumer($context, $destination);
        $consumer->reject($message, true);
    }

    public function testShouldRejectMessageAndRequeueWithVisibilityTimeout()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('changeMessageVisibility')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueUrl' => 'theQueueUrl',
                'ReceiptHandle' => 'theReceipt',
                'VisibilityTimeout' => 30,
            ]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;

        $message = new SqsMessage();
        $message->setReceiptHandle('theReceipt');
        $message->setRequeueVisibilityTimeout(30);

        $destination = new SqsDestination('queue');
        $destination->setRegion('theRegion');

        $consumer = new SqsConsumer($context, $destination);
        $consumer->reject($message, true);
    }

    public function testShouldReceiveMessage()
    {
        $expectedAttributes = [
            '@region' => null,
            'AttributeNames' => ['All'],
            'MessageAttributeNames' => ['All'],
            'MaxNumberOfMessages' => 1,
            'QueueUrl' => 'theQueueUrl',
            'WaitTimeSeconds' => 0,
        ];

        $expectedSqsMessage = [
            'Body' => 'The Body',
            'ReceiptHandle' => 'The Receipt',
            'MessageId' => 'theMessageId',
            'Attributes' => [
                'SenderId' => 'AROAX5IAWYILCTYIS3OZ5:foo@bar.com',
                'ApproximateFirstReceiveTimestamp' => '1560512269481',
                'ApproximateReceiveCount' => '3',
                'SentTimestamp' => '1560512260079',
            ],
            'MessageAttributes' => [
                'Headers' => [
                    'StringValue' => json_encode([['hkey' => 'hvalue'], ['key' => 'value']]),
                    'DataType' => 'String',
                ],
            ],
        ];

        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('receiveMessage')
            ->with($this->identicalTo($expectedAttributes))
            ->willReturn(new Result(['Messages' => [$expectedSqsMessage]]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new SqsMessage())
        ;

        $consumer = new SqsConsumer($context, new SqsDestination('queue'));
        $result = $consumer->receiveNoWait();

        $this->assertInstanceOf(SqsMessage::class, $result);
        $this->assertEquals('The Body', $result->getBody());
        $this->assertEquals(['hkey' => 'hvalue', 'message_id' => 'theMessageId'], $result->getHeaders());
        $this->assertEquals(['key' => 'value'], $result->getProperties());
        $this->assertEquals([
            'SenderId' => 'AROAX5IAWYILCTYIS3OZ5:foo@bar.com',
            'ApproximateFirstReceiveTimestamp' => '1560512269481',
            'ApproximateReceiveCount' => '3',
            'SentTimestamp' => '1560512260079',
        ], $result->getAttributes());
        $this->assertTrue($result->isRedelivered());
        $this->assertEquals('The Receipt', $result->getReceiptHandle());
        $this->assertEquals('theMessageId', $result->getMessageId());
    }

    public function testShouldReceiveMessageWithCustomRegion()
    {
        $expectedAttributes = [
            '@region' => 'theRegion',
            'AttributeNames' => ['All'],
            'MessageAttributeNames' => ['All'],
            'MaxNumberOfMessages' => 1,
            'QueueUrl' => 'theQueueUrl',
            'WaitTimeSeconds' => 0,
        ];

        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('receiveMessage')
            ->with($this->identicalTo($expectedAttributes))
            ->willReturn(new Result(['Messages' => [[
                'Body' => 'The Body',
                'ReceiptHandle' => 'The Receipt',
                'MessageId' => 'theMessageId',
                'Attributes' => [
                    'ApproximateReceiveCount' => 3,
                ],
                'MessageAttributes' => [
                    'Headers' => [
                        'StringValue' => json_encode([['hkey' => 'hvalue'], ['key' => 'value']]),
                        'DataType' => 'String',
                    ],
                ],
            ]]]))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new SqsMessage())
        ;

        $destination = new SqsDestination('queue');
        $destination->setRegion('theRegion');

        $consumer = new SqsConsumer($context, $destination);
        $result = $consumer->receiveNoWait();

        $this->assertInstanceOf(SqsMessage::class, $result);
    }

    public function testShouldReturnNullIfThereIsNoNewMessage()
    {
        $expectedAttributes = [
            '@region' => null,
            'AttributeNames' => ['All'],
            'MessageAttributeNames' => ['All'],
            'MaxNumberOfMessages' => 1,
            'QueueUrl' => 'theQueueUrl',
            'WaitTimeSeconds' => 10,
        ];

        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('receiveMessage')
            ->with($this->identicalTo($expectedAttributes))
            ->willReturn(new Result())
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getSqsClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->never())
            ->method('createMessage')
        ;

        $consumer = new SqsConsumer($context, new SqsDestination('queue'));
        $result = $consumer->receive(10000);

        $this->assertNull($result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SqsProducer
     */
    private function createProducerMock(): SqsProducer
    {
        return $this->createMock(SqsProducer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SqsClient
     */
    private function createSqsClientMock(): SqsClient
    {
        return $this->createMock(SqsClient::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SqsContext
     */
    private function createContextMock(): SqsContext
    {
        return $this->createMock(SqsContext::class);
    }
}
