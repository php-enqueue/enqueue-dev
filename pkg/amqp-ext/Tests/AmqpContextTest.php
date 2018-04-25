<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConsumer;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\AmqpExt\Buffer;
use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use PHPUnit\Framework\TestCase;

class AmqpContextTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementPsrContextInterface()
    {
        $this->assertClassImplements(PsrContext::class, AmqpContext::class);
    }

    public function testCouldBeConstructedWithExtChannelAsFirstArgument()
    {
        new AmqpContext($this->createExtChannelMock(), 'basic_get');
    }

    public function testCouldBeConstructedWithExtChannelCallbackFactoryAsFirstArgument()
    {
        new AmqpContext(function () {
            return $this->createExtChannelMock();
        }, 'basic_get');
    }

    public function testShouldCreateNewBufferOnConstruct()
    {
        $context = new AmqpContext(function () {
            return $this->createExtChannelMock();
        }, 'basic_get');

        $this->assertAttributeInstanceOf(Buffer::class, 'buffer', $context);
    }

    public function testThrowIfNeitherCallbackNorExtChannelAsFirstArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The extChannel argument must be either AMQPChannel or callable that return AMQPChannel.');

        new AmqpContext(new \stdClass(), 'basic_get');
    }

    public function testShouldReturnAmqpMessageOnCreateMessageCallWithoutArguments()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

        $message = $context->createMessage();

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
    }

    public function testShouldReturnAmqpMessageOnCreateMessageCal()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

        $message = $context->createMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['bar' => 'barVal'], $message->getHeaders());
        $this->assertSame(['foo' => 'fooVal'], $message->getProperties());
    }

    public function testShouldCreateTopicWithGivenName()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

        $topic = $context->createTopic('theName');

        $this->assertInstanceOf(AmqpTopic::class, $topic);
        $this->assertSame('theName', $topic->getTopicName());
        $this->assertSame(AmqpTopic::FLAG_NOPARAM, $topic->getFlags());
        $this->assertSame([], $topic->getArguments());
    }

    public function testShouldCreateQueueWithGivenName()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

        $queue = $context->createQueue('theName');

        $this->assertInstanceOf(AmqpQueue::class, $queue);
        $this->assertSame('theName', $queue->getQueueName());
        $this->assertSame(AmqpQueue::FLAG_NOPARAM, $queue->getFlags());
        $this->assertSame([], $queue->getArguments());
        $this->assertNull($queue->getConsumerTag());
    }

    public function testShouldReturnAmqpProducer()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

        $producer = $context->createProducer();

        $this->assertInstanceOf(AmqpProducer::class, $producer);
    }

    public function testShouldReturnAmqpConsumerForGivenQueue()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

        $buffer = $this->readAttribute($context, 'buffer');

        $queue = new AmqpQueue('aName');

        $consumer = $context->createConsumer($queue);

        $this->assertInstanceOf(AmqpConsumer::class, $consumer);
        $this->assertAttributeSame($context, 'context', $consumer);
        $this->assertAttributeSame($queue, 'queue', $consumer);
        $this->assertAttributeSame($queue, 'queue', $consumer);
        $this->assertAttributeSame($buffer, 'buffer', $consumer);
    }

    public function testShouldThrowIfNotAmqpQueueGivenOnCreateConsumerCall()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Interop\Amqp\AmqpQueue but got Enqueue\Null\NullQueue.');
        $context->createConsumer(new NullQueue('aName'));
    }

    public function testShouldThrowIfNotAmqpTopicGivenOnCreateConsumerCall()
    {
        $context = new AmqpContext($this->createExtChannelMock(), 'basic_get');

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

        $context = new AmqpContext($extChannelMock, 'basic_get');

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

        $context = new AmqpContext($extChannelMock, 'basic_get');

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

        $context = new AmqpContext($extChannelMock, 'basic_get');

        $context->close();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\AMQPChannel
     */
    private function createExtChannelMock()
    {
        return $this->createMock(\AMQPChannel::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\AMQPChannel
     */
    private function createExtConnectionMock()
    {
        return $this->getMockBuilder(\AMQPConnection::class)
            ->setMethods(['isPersistent', 'isConnected', 'pdisconnect', 'disconnect'])
            ->getMock()
        ;
    }
}
