<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\ExtensionType;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Enqueue\Stomp\StompProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Stomp\Client;
use Stomp\Transport\Message as VendorMessage;

class StompProducerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, StompProducer::class);
    }

    public function testShouldThrowInvalidDestinationExceptionWhenDestinationIsWrongType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of');

        $producer = new StompProducer($this->createStompClientMock());

        $producer->send($this->createMock(Queue::class), new StompMessage());
    }

    public function testShouldThrowInvalidMessageExceptionWhenMessageIsWrongType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of');

        $producer = new StompProducer($this->createStompClientMock());

        $producer->send(new StompDestination(ExtensionType::RABBITMQ), $this->createMock(Message::class));
    }

    public function testShouldSendMessage()
    {
        $client = $this->createStompClientMock();
        $client
            ->expects($this->once())
            ->method('send')
            ->with('/queue/name', $this->isInstanceOf(VendorMessage::class))
        ;

        $producer = new StompProducer($client);

        $destination = new StompDestination(ExtensionType::RABBITMQ);
        $destination->setType(StompDestination::TYPE_QUEUE);
        $destination->setStompName('name');

        $producer->send($destination, new StompMessage('body'));
    }

    public function testShouldEncodeMessageHeadersAndProperties()
    {
        $stompMessage = null;
        $client = $this->createStompClientMock();
        $client
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($destination, VendorMessage $message) use (&$stompMessage) {
                $stompMessage = $message;
            })
        ;

        $producer = new StompProducer($client);

        $message = new StompMessage('', ['key' => 'value'], ['hkey' => false]);

        $destination = new StompDestination(ExtensionType::RABBITMQ);
        $destination->setType(StompDestination::TYPE_QUEUE);
        $destination->setStompName('name');

        $producer->send($destination, $message);

        $expectedHeaders = [
            'hkey' => 'false',
            'durable' => 'false',
            'auto-delete' => 'true',
            'exclusive' => 'false',
            '_type_hkey' => 'b',
            '_type_durable' => 'b',
            '_type_auto-delete' => 'b',
            '_type_exclusive' => 'b',
            '_property_key' => 'value',
            '_property__type_key' => 's',
        ];

        $this->assertEquals($expectedHeaders, $stompMessage->getHeaders());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Client
     */
    private function createStompClientMock()
    {
        return $this->createMock(Client::class);
    }
}
