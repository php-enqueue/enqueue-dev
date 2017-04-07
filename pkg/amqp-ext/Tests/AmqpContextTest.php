<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConsumer;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\AmqpMessage;
use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\AmqpExt\AmqpQueue;
use Enqueue\AmqpExt\AmqpTopic;
use Enqueue\AmqpExt\Buffer;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullQueue;
use Enqueue\Transport\Null\NullTopic;

class AmqpContextTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementPsrContextInterface()
    {
        $this->assertClassImplements(PsrContext::class, AmqpContext::class);
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

    public function testShouldCreateNewBufferOnConstruct()
    {
        $context = new AmqpContext(function () {
            return $this->createExtChannelMock();
        });

        $this->assertAttributeInstanceOf(Buffer::class, 'buffer', $context);
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
        $this->assertSame(\AMQP_NOPARAM, $topic->getFlags());
        $this->assertSame([], $topic->getArguments());
        $this->assertSame(null, $topic->getRoutingKey());
    }

    public function testShouldThrowIfNotAmqpTopicGivenOnDeleteTopicCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpTopic but got Enqueue\Transport\Null\NullTopic.');
        $context->deleteTopic(new NullTopic('aName'));
    }

    public function testShouldThrowIfNotAmqpTopicGivenOnDeclareTopicCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpTopic but got Enqueue\Transport\Null\NullTopic.');
        $context->declareTopic(new NullTopic('aName'));
    }

    public function testShouldCreateQueueWithGivenName()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $queue = $context->createQueue('theName');

        $this->assertInstanceOf(AmqpQueue::class, $queue);
        $this->assertSame('theName', $queue->getQueueName());
        $this->assertSame(\AMQP_NOPARAM, $queue->getFlags());
        $this->assertSame([], $queue->getArguments());
        $this->assertSame([], $queue->getBindArguments());
        $this->assertSame(null, $queue->getConsumerTag());
    }

    public function testShouldThrowIfNotAmqpQueueGivenOnDeleteQueueCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpQueue but got Enqueue\Transport\Null\NullQueue.');
        $context->deleteQueue(new NullQueue('aName'));
    }

    public function testShouldThrowIfNotAmqpQueueGivenOnDeclareQueueCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpQueue but got Enqueue\Transport\Null\NullQueue.');
        $context->declareQueue(new NullQueue('aName'));
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
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpQueue but got Enqueue\Transport\Null\NullQueue.');
        $context->createConsumer(new NullQueue('aName'));
    }

    public function testShouldThrowIfNotAmqpTopicGivenOnCreateConsumerCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpTopic but got Enqueue\Transport\Null\NullTopic.');
        $context->createConsumer(new NullTopic('aName'));
    }

    public function shouldDoNothingIfConnectionAlreadyClosed()
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

    public function testShouldThrowIfSourceNotAmqpTopicOnBindCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpTopic but got Enqueue\Transport\Null\NullTopic.');
        $context->bind(new NullTopic('aName'), new AmqpQueue('aName'));
    }

    public function testShouldThrowIfTargetNotAmqpQueueOnBindCall()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpQueue but got Enqueue\Transport\Null\NullQueue.');
        $context->bind(new AmqpTopic('aName'), new NullQueue('aName'));
    }

    public function testShouldThrowIfGivenQueueNotAmqpQueueOnPurge()
    {
        $context = new AmqpContext($this->createExtChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpExt\AmqpQueue but got Enqueue\Transport\Null\NullQueue.');
        $context->purge(new NullQueue('aName'));
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
        return $this->createMock(\AMQPConnection::class);
    }
}
