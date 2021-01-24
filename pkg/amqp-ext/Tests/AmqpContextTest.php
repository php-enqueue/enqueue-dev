<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConsumer;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\AmqpExt\AmqpSubscriptionConsumer;
use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidDestinationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpContextTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementQueueInteropContextInterface()
    {
        $this->assertClassImplements(Context::class, AmqpContext::class);
    }

    public function testCouldBeConstructedWithExtChannelAsFirstArgument()
    {
        new AmqpContext($this->createExtChannelMock());
    }

    public function testCouldBeConstructedWithExtChannelCallbackFactoryAsFirstArgument()
    {
        new AmqpContext(function () {
            return $this->createExtChannelMock();
        });
    }

    public function testThrowIfNeitherCallbackNorExtChannelAsFirstArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The extChannel argument must be either AMQPChannel or callable that return AMQPChannel.');

        new AmqpContext(new \stdClass());
    }

    public function testShouldReturnAmqpMessageOnCreateMessageCallWithoutArguments()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $message = $context->createMessage();

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
    }

    public function testShouldReturnAmqpMessageOnCreateMessageCal()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $message = $context->createMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['bar' => 'barVal'], $message->getHeaders());
        $this->assertSame(['foo' => 'fooVal'], $message->getProperties());
    }

    public function testShouldCreateTopicWithGivenName()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $topic = $context->createTopic('theName');

        $this->assertInstanceOf(AmqpTopic::class, $topic);
        $this->assertSame('theName', $topic->getTopicName());
        $this->assertSame(AmqpTopic::FLAG_NOPARAM, $topic->getFlags());
        $this->assertSame([], $topic->getArguments());
    }

    public function testShouldCreateQueueWithGivenName()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $queue = $context->createQueue('theName');

        $this->assertInstanceOf(AmqpQueue::class, $queue);
        $this->assertSame('theName', $queue->getQueueName());
        $this->assertSame(AmqpQueue::FLAG_NOPARAM, $queue->getFlags());
        $this->assertSame([], $queue->getArguments());
        $this->assertNull($queue->getConsumerTag());
    }

    public function testShouldReturnAmqpProducer()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $producer = $context->createProducer();

        $this->assertInstanceOf(AmqpProducer::class, $producer);
    }

    public function testShouldReturnAmqpConsumerForGivenQueue()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $queue = new AmqpQueue('aName');

        $consumer = $context->createConsumer($queue);

        $this->assertInstanceOf(AmqpConsumer::class, $consumer);
        $this->assertAttributeSame($context, 'context', $consumer);
        $this->assertAttributeSame($queue, 'queue', $consumer);
    }

    public function testShouldThrowIfNotAmqpQueueGivenOnCreateConsumerCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Interop\Amqp\AmqpQueue but got Enqueue\Null\NullQueue.');
        $context->createConsumer(new NullQueue('aName'));
    }

    public function testShouldThrowIfNotAmqpTopicGivenOnCreateConsumerCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Interop\Amqp\AmqpTopic but got Enqueue\Null\NullTopic.');
        $context->createConsumer(new NullTopic('aName'));
    }

    public function testShouldDoNothingIfConnectionAlreadyClosed()
    {
        $extConnectionMock = $this->createExtConnectionMock();
        $extConnectionMock
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false)
        ;
        $extConnectionMock
            ->expects($this->never())
            ->method('isPersistent')
        ;
        $extConnectionMock
            ->expects($this->never())
            ->method('pdisconnect')
        ;
        $extConnectionMock
            ->expects($this->never())
            ->method('disconnect')
        ;

        $extChannelMock = $this->createExtChannelMock();
        $extChannelMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($extConnectionMock)
        ;

        $context = new AmqpContext($extChannelMock);

        $context->close();
    }

    public function testShouldCloseNotPersistedConnection()
    {
        $extConnectionMock = $this->createExtConnectionMock();
        $extConnectionMock
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(true)
        ;
        $extConnectionMock
            ->expects($this->once())
            ->method('isPersistent')
            ->willReturn(false)
        ;
        $extConnectionMock
            ->expects($this->never())
            ->method('pdisconnect')
        ;
        $extConnectionMock
            ->expects($this->once())
            ->method('disconnect')
        ;

        $extChannelMock = $this->createExtChannelMock();
        $extChannelMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($extConnectionMock)
        ;

        $context = new AmqpContext($extChannelMock);

        $context->close();
    }

    public function testShouldClosePersistedConnection()
    {
        $extConnectionMock = $this->createExtConnectionMock();
        $extConnectionMock
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(true)
        ;
        $extConnectionMock
            ->expects($this->once())
            ->method('isPersistent')
            ->willReturn(true)
        ;
        $extConnectionMock
            ->expects($this->once())
            ->method('pdisconnect')
        ;
        $extConnectionMock
            ->expects($this->never())
            ->method('disconnect')
        ;

        $extChannelMock = $this->createExtChannelMock();
        $extChannelMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($extConnectionMock)
        ;

        $context = new AmqpContext($extChannelMock);

        $context->close();
    }

    public function testShouldReturnExpectedSubscriptionConsumerInstance()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->assertInstanceOf(AmqpSubscriptionConsumer::class, $context->createSubscriptionConsumer());
    }

    /**
     * @return MockObject|\AMQPChannel
     */
    private function createExtChannelMock()
    {
        return $this->createMock(\AMQPChannel::class);
    }

    /**
     * @return MockObject|\AMQPChannel
     */
    private function createExtConnectionMock()
    {
        return $this->getMockBuilder(\AMQPConnection::class)
            ->setMethods(['isPersistent', 'isConnected', 'pdisconnect', 'disconnect'])
            ->getMock()
        ;
    }
}
