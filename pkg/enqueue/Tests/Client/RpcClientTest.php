<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\NullDriver;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\RpcClient;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrContext;
use Enqueue\Rpc\Promise;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;

class RpcClientTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithPsrContextDriverAndProducerAsArguments()
    {
        new RpcClient(
            new NullDriver(new NullContext(), Config::create()),
            $this->createProducerMock(),
            $this->createPsrContextMock()
        );
    }

    public function testShouldSetReplyToIfNotSet()
    {
        $context = new NullContext();

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic, Message $message) {
                $this->assertNotEmpty($message->getHeader('correlation_id'));
            })
        ;

        $rpc = new RpcClient(
            new NullDriver($context, Config::create()),
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', 'aMessage', 2);
    }

    public function testShouldNotSetReplyToIfSet()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setHeader('reply_to', 'rpc.reply');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic, Message $message) {
                $this->assertEquals('rpc.reply', $message->getHeader('reply_to'));
            })
        ;

        $rpc = new RpcClient(
            new NullDriver($context, Config::create()),
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', $message, 2);
    }

    public function testShouldSetCorrelationIdIfNotSet()
    {
        $context = new NullContext();

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic, Message $message) {
                $this->assertNotEmpty($message->getHeader('correlation_id'));
            })
        ;

        $rpc = new RpcClient(
            new NullDriver($context, Config::create()),
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', 'aMessage', 2);
    }

    public function testShouldNotSetCorrelationIdIfSet()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setHeader('correlation_id', 'theCorrelationId');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic, Message $message) {
                $this->assertEquals('theCorrelationId', $message->getHeader('correlation_id'));
            })
        ;

        $rpc = new RpcClient(
            new NullDriver($context, Config::create()),
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', $message, 2);
    }

    public function testShouldPopulatePromiseWithExpectedArguments()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setHeader('correlation_id', 'theCorrelationId');
        $message->getHeader('reply_to', 'theReplyTo');

        $timeout = 123;

        $rpc = new RpcClient(
            new NullDriver($context, Config::create()),
            $this->createProducerMock(),
            $context
        );

        $promise = $rpc->callAsync('aTopic', $message, $timeout);

        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertAttributeEquals('theCorrelationId', 'correlationId', $promise);
        $this->assertAttributeEquals(123, 'timeout', $promise);
        $this->assertAttributeInstanceOf(PsrConsumer::class, 'consumer', $promise);
    }

    public function testShouldDoSyncCall()
    {
        $timeout = 123;
        $replyMessage = new NullMessage();

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects($this->once())
            ->method('getMessage')
            ->willReturn($replyMessage)
        ;

        /** @var \PHPUnit_Framework_MockObject_MockObject|RpcClient $rpc */
        $rpc = $this->getMockBuilder(RpcClient::class)->disableOriginalConstructor()->setMethods(['callAsync'])->getMock();
        $rpc
            ->expects($this->once())
            ->method('callAsync')
            ->with('theTopic', 'theMessage', $timeout)
            ->willReturn($promiseMock)
        ;

        $actualReplyMessage = $rpc->call('theTopic', 'theMessage', $timeout);

        $this->assertSame($replyMessage, $actualReplyMessage);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
