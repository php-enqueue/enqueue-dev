<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\RpcClient;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrContext;
use Enqueue\Rpc\Promise;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;
use PHPUnit\Framework\TestCase;

class RpcClientTest extends TestCase
{
    public function testCouldBeConstructedWithPsrContextDriverAndProducerAsArguments()
    {
        new RpcClient(
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
                $this->assertNotEmpty($message->getReplyTo());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', new Message(), 2);
    }

    public function testShouldNotSetReplyToIfSet()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setReplyTo('theReplyTo');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic, Message $message) {
                $this->assertEquals('theReplyTo', $message->getReplyTo());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', $message, 2);
    }

    public function testShouldUseSameTopicOnProducerSendCall()
    {
        $context = new NullContext();

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic) {
                $this->assertEquals('theTopic', $topic);
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('theTopic', new Message(), 2);
    }

    public function testShouldSetCorrelationIdIfNotSet()
    {
        $context = new NullContext();

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic, Message $message) {
                $this->assertNotEmpty($message->getCorrelationId());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', new Message(), 2);
    }

    public function testShouldNotSetCorrelationIdIfSet()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setCorrelationId('theCorrelationId');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function($topic, Message $message) {
                $this->assertEquals('theCorrelationId', $message->getCorrelationId());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', $message, 2);
    }

    public function testShouldPopulatePromiseWithExpectedArguments()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setCorrelationId('theCorrelationId');

        $timeout = 123;

        $rpc = new RpcClient(
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
