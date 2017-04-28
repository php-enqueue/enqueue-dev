<?php
namespace Enqueue\Sqs\Tests;

use Aws\Result;
use Aws\Sqs\SqsClient;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrMessage;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Enqueue\Test\ClassExtensionTrait;

class SqsConsumerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, SqsConsumer::class);
    }

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
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Sqs\SqsMessage but it is Mock_PsrMessage');

        $consumer = new SqsConsumer($this->createContextMock(), new SqsDestination('queue'));
        $consumer->acknowledge($this->createMock(PsrMessage::class));
    }

    public function testCouldAcknowledgeMessage()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('deleteMessage')
            ->with($this->identicalTo(['QueueUrl' => 'theQueueUrl', 'ReceiptHandle' => 'theReceipt']))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
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

    public function testRejectShouldThrowIfInstanceOfMessageIsInvalid()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Sqs\SqsMessage but it is Mock_PsrMessage');

        $consumer = new SqsConsumer($this->createContextMock(), new SqsDestination('queue'));
        $consumer->reject($this->createMock(PsrMessage::class));
    }

    public function testShouldRejectMessage()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('deleteMessage')
            ->with($this->identicalTo(['QueueUrl' => 'theQueueUrl', 'ReceiptHandle' => 'theReceipt']))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
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

    public function testShouldRejectMessageAndRequeue()
    {
        $client = $this->createSqsClientMock();
        $client
            ->expects($this->once())
            ->method('deleteMessage')
            ->with($this->identicalTo(['QueueUrl' => 'theQueueUrl', 'ReceiptHandle' => 'theReceipt']))
        ;

        $message = new SqsMessage();
        $message->setReceiptHandle('theReceipt');

        $destination = new SqsDestination('queue');

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($destination), $this->identicalTo($message))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;
        $context
            ->expects($this->once())
            ->method('getQueueUrl')
            ->willReturn('theQueueUrl')
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;

        $consumer = new SqsConsumer($context, $destination);
        $consumer->reject($message, true);
    }

    public function testShouldReceiveMessage()
    {
        $expectedAttributes = [
            'AttributeNames' => ['All'],
            'MessageAttributeNames' => ['All'],
            'MaxNumberOfMessages' => 1,
            'QueueUrl' => 'theQueueUrl',
            'WaitTimeSeconds' => 0,
        ];

        $expectedSqsMessage = [
            'Body' => 'The Body',
            'ReceiptHandle' => 'The Receipt',
            'Attributes' => [
                'ApproximateReceiveCount' => 3,
            ],
            'MessageAttributes' => [
                'Headers' => [
                    'StringValue' => json_encode([['hkey' => 'hvalue'], ['key' => 'value']]),
                    'DataType' => 'String'
                ],
            ]
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
            ->method('getClient')
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
        $this->assertEquals(['hkey' => 'hvalue'], $result->getHeaders());
        $this->assertEquals(['key' => 'value'], $result->getProperties());
        $this->assertTrue($result->isRedelivered());
        $this->assertEquals('The Receipt', $result->getReceiptHandle());
    }

    public function testShouldReturnNullIfThereIsNoNewMessage()
    {
        $expectedAttributes = [
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
            ->method('getClient')
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
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(SqsProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsClient
     */
    private function createSqsClientMock()
    {
        return $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteMessage', 'receiveMessage'])
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsContext
     */
    private function createContextMock()
    {
        return $this->createMock(SqsContext::class);
    }
}
