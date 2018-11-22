<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\AzureStorage\AzureStorageConsumer;
use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\AzureStorage\AzureStorageMessage;
use Enqueue\AzureStorage\AzureStorageProducer;

use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesResult;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;

class AzureStorageConsumerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, AzureStorageConsumer::class);
    }

    public function testCouldBeConstructedWithContextAndDestinationAndPreFetchCountAsArguments()
    {
        new AzureStorageConsumer($this->createQueueRestProxyMock(), new AzureStorageDestination('aQueue'));
    }

    public function testShouldReturnDestinationSetInConstructorOnGetQueue()
    {
        $destination = new AzureStorageDestination('aQueue');

        $consumer = new AzureStorageConsumer($this->createQueueRestProxyMock(), $destination);

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testShouldAlwaysReturnNullOnReceiveNoWait()
    {
        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);

        $listMessagesResultMock = $this->createMock(ListMessagesResult::class);
        $listMessagesResultMock
            ->expects($this->any())
            ->method('getQueueMessages')
            ->willReturn([])
        ;

        $azureMock = $this->createQueueRestProxyMock();
        $azureMock
            ->expects($this->any())
            ->method('listMessages')
            ->with('aQueue', $options)
            ->willReturn($listMessagesResultMock)
        ;

        $consumer = new AzureStorageConsumer($azureMock, new AzureStorageDestination('aQueue'));

        $this->assertNull($consumer->receiveNoWait());
        $this->assertNull($consumer->receiveNoWait());
        $this->assertNull($consumer->receiveNoWait());
    }

    public function testShouldDoNothingOnAcknowledge()
    {
        $consumer = new AzureStorageConsumer($this->createQueueRestProxyMock(), new AzureStorageDestination('aQueue'));

        $consumer->acknowledge(new AzureStorageMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $consumer = new AzureStorageConsumer($this->createQueueRestProxyMock(), new AzureStorageDestination('aQueue'));

        $consumer->reject(new AzureStorageMessage());
    }

    public function testShouldQueueMsgAgainReject()
    {
        $messageMock = $this->createQueueMessageMock();

        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);

        $listMessagesResultMock = $this->createMock(ListMessagesResult::class);
        $listMessagesResultMock
            ->expects($this->any())
            ->method('getQueueMessages')
            ->willReturn([$messageMock])
        ;
        $createMessageResultMock = $this->createMock(CreateMessageResult::class);
        $createMessageResultMock
            ->expects($this->any())
            ->method('getQueueMessage')
            ->willReturn($messageMock)
        ;

        $azureMock = $this->createQueueRestProxyMock();
        $azureMock
            ->expects($this->any())
            ->method('listMessages')
            ->with('aQueue', $options)
            ->willReturn($listMessagesResultMock)
        ;
         $azureMock
            ->expects($this->any())
            ->method('createMessage')
            ->with('aQueue', $messageMock->getMessageText())
            ->willReturn($createMessageResultMock)
        ;
 
        $consumer = new AzureStorageConsumer($azureMock, new AzureStorageDestination('aQueue'));

        $receivedMessage = $consumer->receiveNoWait();

        $consumer->reject($receivedMessage, true);

        $this->assertInstanceOf(AzureStorageMessage::class, $receivedMessage);
        $this->assertSame('aBody', $receivedMessage->getBody());
    }

    public function testShouldReturnMsgOnReceiveNoWait()
    {
        $messageMock = $this->createQueueMessageMock();

        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);

        $listMessagesResultMock = $this->createMock(ListMessagesResult::class);
        $listMessagesResultMock
            ->expects($this->any())
            ->method('getQueueMessages')
            ->willReturn([$messageMock])
        ;

        $azureMock = $this->createQueueRestProxyMock();
        $azureMock
            ->expects($this->any())
            ->method('listMessages')
            ->with('aQueue', $options)
            ->willReturn($listMessagesResultMock)
        ;

        $consumer = new AzureStorageConsumer($azureMock, new AzureStorageDestination('aQueue'));

        $receivedMessage = $consumer->receiveNoWait();
        $this->assertInstanceOf(AzureStorageMessage::class, $receivedMessage);
        $this->assertSame('aBody', $receivedMessage->getBody());
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
