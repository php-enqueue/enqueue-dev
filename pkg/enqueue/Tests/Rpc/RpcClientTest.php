<?php
namespace Enqueue\Tests\Rpc;

use Enqueue\Psr\Consumer;
use Enqueue\Psr\Context;
use Enqueue\Psr\Producer;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcClient;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;
use Enqueue\Transport\Null\NullQueue;

class RpcClientTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithPsrContextAsFirstArgument()
    {
        new RpcClient($this->createPsrContextMock());
    }

    public function testShouldSetReplyToIfNotSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertNotEmpty($message->getReplyTo());
    }

    public function testShouldNotSetReplyToIfSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();
        $message->setReplyTo('rpc.reply');

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertEquals('rpc.reply', $message->getReplyTo());
    }

    public function testShouldSetCorrelationIdIfNotSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertNotEmpty($message->getCorrelationId());
    }

    public function testShouldNotSetCorrelationIdIfSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();
        $message->setCorrelationId('theCorrelationId');

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertEquals('theCorrelationId', $message->getCorrelationId());
    }

    public function testShouldPopulatePromiseWithExpectedArguments()
    {
        $context = new NullContext();

        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $timeout = 123;

        $rpc = new RpcClient($context);

        $promise = $rpc->callAsync($queue, $message, $timeout);

        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertAttributeEquals('theCorrelationId', 'correlationId', $promise);
        $this->assertAttributeEquals(123, 'timeout', $promise);
        $this->assertAttributeInstanceOf(Consumer::class, 'consumer', $promise);
    }

    public function testShouldProduceMessageToQueueAndCreateConsumerForReplyQueue()
    {
        $queue = new NullQueue('aQueue');
        $replyQueue = new NullQueue('theReplyTo');
        $message = new NullMessage();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $producer = $this->createPsrProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($message))
        ;

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('theReplyTo')
            ->willReturn($replyQueue)
        ;
        $context
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($replyQueue))
            ->willReturn($this->createPsrConsumerMock())
        ;

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);
    }

    public function testShouldDoSyncCall()
    {
        $timeout = 123;
        $message = new NullMessage();
        $queue = new NullQueue('aName');
        $replyMessage = new NullMessage();

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects($this->once())
            ->method('getMessage')
            ->willReturn($replyMessage)
        ;

        $rpc = $this->getMockBuilder(RpcClient::class)->disableOriginalConstructor()->setMethods(['callAsync'])->getMock();
        $rpc
            ->expects($this->once())
            ->method('callAsync')
            ->with($this->identicalTo($queue), $this->identicalTo($message), $timeout)
            ->willReturn($promiseMock)
        ;

        $actualReplyMessage = $rpc->call($queue, $message, $timeout);

        $this->assertSame($replyMessage, $actualReplyMessage);
    }

    /**
     * @return Context|\PHPUnit_Framework_MockObject_MockObject|Producer
     */
    private function createPsrProducerMock()
    {
        return $this->createMock(Producer::class);
    }

    /**
     * @return \Enqueue\Psr\Context|\PHPUnit_Framework_MockObject_MockObject|Consumer
     */
    private function createPsrConsumerMock()
    {
        return $this->createMock(Consumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    private function createPsrContextMock()
    {
        return $this->createMock(Context::class);
    }
}
