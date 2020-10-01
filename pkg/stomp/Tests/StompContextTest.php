<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\BufferedStompClient;
use Enqueue\Stomp\ExtensionType;
use Enqueue\Stomp\StompConsumer;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Enqueue\Stomp\StompProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Queue;

class StompContextTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSessionInterface()
    {
        $this->assertClassImplements(Context::class, StompContext::class);
    }

    public function testCouldBeCreatedWithRequiredArguments()
    {
        new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
    }

    public function testCouldBeConstructedWithExtChannelCallbackFactoryAsFirstArgument()
    {
        new StompContext(function () {
            return $this->createStompClientMock();
        }, ExtensionType::RABBITMQ);
    }

    public function testThrowIfNeitherCallbackNorExtChannelAsFirstArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The stomp argument must be either BufferedStompClient or callable that return BufferedStompClient.');

        new StompContext(new \stdClass(), ExtensionType::RABBITMQ);
    }

    public function testShouldCreateMessageInstance()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $message = $context->createMessage('the body', ['key' => 'value'], ['hkey' => 'hvalue']);

        $this->assertInstanceOf(StompMessage::class, $message);
        $this->assertSame('the body', $message->getBody());
        $this->assertSame(['hkey' => 'hvalue'], $message->getHeaders());
        $this->assertSame(['key' => 'value'], $message->getProperties());
    }

    public function testShouldCreateQueueInstance()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $queue = $context->createQueue('the name');

        $this->assertInstanceOf(StompDestination::class, $queue);
        $this->assertSame('/queue/the name', $queue->getQueueName());
        $this->assertSame('/queue/the name', $queue->getTopicName());
        $this->assertSame(StompDestination::TYPE_QUEUE, $queue->getType());
    }

    public function testCreateQueueShouldCreateDestinationIfNameIsFullDestinationString()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $destination = $context->createQueue('/amq/queue/name/routing-key');

        $this->assertInstanceOf(StompDestination::class, $destination);
        $this->assertEquals('amq/queue', $destination->getType());
        $this->assertEquals('name', $destination->getStompName());
        $this->assertEquals('routing-key', $destination->getRoutingKey());
        $this->assertEquals('/amq/queue/name/routing-key', $destination->getQueueName());
    }

    public function testShouldCreateTopicInstanceWithExchangePrefix()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $topic = $context->createTopic('the name');

        $this->assertInstanceOf(StompDestination::class, $topic);
        $this->assertSame('/exchange/the name', $topic->getQueueName());
        $this->assertSame('/exchange/the name', $topic->getTopicName());
        $this->assertSame(StompDestination::TYPE_EXCHANGE, $topic->getType());
    }

    public function testShouldCreateTopicInstanceWithTopicPrefix()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::ACTIVEMQ);

        $topic = $context->createTopic('the name');

        $this->assertInstanceOf(StompDestination::class, $topic);
        $this->assertSame('/topic/the name', $topic->getQueueName());
        $this->assertSame('/topic/the name', $topic->getTopicName());
        $this->assertSame(StompDestination::TYPE_TOPIC, $topic->getType());
    }

    public function testCreateTopicShouldCreateDestinationIfNameIsFullDestinationString()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $destination = $context->createTopic('/amq/queue/name/routing-key');

        $this->assertInstanceOf(StompDestination::class, $destination);
        $this->assertEquals('amq/queue', $destination->getType());
        $this->assertEquals('name', $destination->getStompName());
        $this->assertEquals('routing-key', $destination->getRoutingKey());
        $this->assertEquals('/amq/queue/name/routing-key', $destination->getQueueName());
    }

    public function testThrowInvalidDestinationException()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of');

        $session = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $session->createConsumer($this->createMock(Queue::class));
    }

    public function testShouldCreateMessageConsumerInstance()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $this->assertInstanceOf(StompConsumer::class, $context->createConsumer($this->createDummyDestination()));
    }

    public function testShouldCreateMessageProducerInstance()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $this->assertInstanceOf(StompProducer::class, $context->createProducer());
    }

    public function testShouldCloseConnections()
    {
        $client = $this->createStompClientMock();
        $client
            ->expects($this->atLeastOnce())
            ->method('disconnect')
        ;

        $context = new StompContext($client, ExtensionType::RABBITMQ);

        $context->createProducer();
        $context->createConsumer($this->createDummyDestination());

        $context->close();
    }

    public function testCreateDestinationShouldThrowLogicExceptionIfTypeIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Destination name is invalid, cant find type: "/invalid-type/name"');

        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $context->createDestination('/invalid-type/name');
    }

    public function testCreateDestinationShouldThrowLogicExceptionIfExtraSlashFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Destination name is invalid, found extra / char: "/queue/name/routing-key/extra');

        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $context->createDestination('/queue/name/routing-key/extra');
    }

    public function testCreateDestinationShouldThrowLogicExceptionIfNameIsEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Destination name is invalid, name is empty: "/queue/"');

        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $context->createDestination('/queue/');
    }

    public function testCreateDestinationShouldThrowLogicExceptionIfRoutingKeyIsEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Destination name is invalid, routing key is empty: "/queue/name/"');

        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $context->createDestination('/queue/name/');
    }

    public function testCreateDestinationShouldParseStringAndCreateDestination()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $destination = $context->createDestination('/amq/queue/name/routing-key');

        $this->assertEquals('amq/queue', $destination->getType());
        $this->assertEquals('name', $destination->getStompName());
        $this->assertEquals('routing-key', $destination->getRoutingKey());
        $this->assertEquals('/amq/queue/name/routing-key', $destination->getQueueName());
    }

    public function testCreateTemporaryQueue()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $tempQueue = $context->createTemporaryQueue();

        $this->assertEquals('temp-queue', $tempQueue->getType());
        $this->assertNotEmpty($tempQueue->getStompName());
        $this->assertEquals('', $tempQueue->getRoutingKey());
        $this->assertEquals('/temp-queue/'.$tempQueue->getStompName(), $tempQueue->getQueueName());
    }

    public function testCreateTemporaryQueuesWithUniqueNames()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);
        $fooTempQueue = $context->createTemporaryQueue();
        $barTempQueue = $context->createTemporaryQueue();

        $this->assertNotEmpty($fooTempQueue->getStompName());
        $this->assertNotEmpty($barTempQueue->getStompName());

        $this->assertNotEquals($fooTempQueue->getStompName(), $barTempQueue->getStompName());
    }

    public function testShouldGetBufferedStompClient()
    {
        $context = new StompContext($this->createStompClientMock(), ExtensionType::RABBITMQ);

        $this->assertInstanceOf(BufferedStompClient::class, $context->getStomp());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|BufferedStompClient
     */
    private function createStompClientMock()
    {
        return $this->createMock(BufferedStompClient::class);
    }

    private function createDummyDestination(): StompDestination
    {
        $destination = new StompDestination(ExtensionType::RABBITMQ);
        $destination->setStompName('aName');
        $destination->setType(StompDestination::TYPE_QUEUE);

        return $destination;
    }
}
