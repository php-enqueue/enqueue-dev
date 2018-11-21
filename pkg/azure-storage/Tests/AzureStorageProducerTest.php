<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\AzureStorage\AzureStorageProducer;
use Enqueue\AzureStorage\AzureStorageMessage;
use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Producer;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use PHPUnit\Framework\TestCase;

class AzureStorageProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, AzureStorageProducer::class);
    }

    public function testCouldBeConstructedWithQueueRestProxy()
    {
        $producer = new AzureStorageProducer($this->createQueueRestProxyMock());

        $this->assertInstanceOf(AzureStorageProducer::class, $producer);
    }

    public function testThrowIfDestinationNotAzureStorageDestinationOnSend()
    {
        $producer = new AzureStorageProducer($this->createQueueRestProxyMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AzureStorage\AzureStorageDestination but got Enqueue\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new AzureStorageMessage());
    }

    public function testThrowIfMessageNotAzureStorageMessageOnSend()
    {
        $producer = new AzureStorageProducer($this->createQueueRestProxyMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\AzureStorage\AzureStorageMessage but it is Enqueue\Null\NullMessage.');
        $producer->send(new AzureStorageDestination('aQueue'), new NullMessage());
    }

    public function testShouldCallCreateMessageOnSend()
    {
        $destination = new AzureStorageDestination('aDestination');
        $message = new AzureStorageMessage();

        $queueMessage = $this->createQueueMessageMock();

        $createMessageResult = $this->createMock(CreateMessageResult::class);
        $createMessageResult
            ->expects($this->once())
            ->method('getQueueMessage')
            ->willReturn($queueMessage);

        $queueRestProxy = $this->createQueueRestProxyMock();
        $queueRestProxy
            ->expects($this->once())
            ->method('createMessage')
            ->with('aDestination', $message->getBody())
            ->willReturn($createMessageResult)
        ;
        
        $producer = new AzureStorageProducer($queueRestProxy);

        $producer->send($destination, $message);
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueRestProxy
     */
    private function createQueueRestProxyMock()
    {
        return $this->createMock(QueueRestProxy::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueMessage
     */
    private function createQueueMessageMock()
    {
        $insertionDateMock = $this->createMock(\DateTime::class);
        $insertionDateMock
            ->expects($this->any())
            ->method('getTimestamp')
            ->willReturn(1542809366);
        
        $messageMock = $this->createMock(QueueMessage::class);
        $messageMock
            ->expects($this->any())
            ->method('getMessageId')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getMessageText')
            ->willReturn('aBody');
        $messageMock
            ->expects($this->any())
            ->method('getInsertionDate')
            ->willReturn($insertionDateMock);
        $messageMock
            ->expects($this->any())
            ->method('getDequeueCount')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getDequeueCount')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getExpirationDate')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getExpirationDate')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getTimeNextVisible')
            ->willReturn('any');
        return $messageMock;
    }
}
