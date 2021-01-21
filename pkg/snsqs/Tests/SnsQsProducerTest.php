<?php

namespace Enqueue\SnsQs\Tests;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsProducer;
use Enqueue\SnsQs\SnsQsMessage;
use Enqueue\SnsQs\SnsQsProducer;
use Enqueue\SnsQs\SnsQsQueue;
use Enqueue\SnsQs\SnsQsTopic;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SnsQsProducerTest extends TestCase
{
    use ClassExtensionTrait;
    use ProphecyTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, SnsQsProducer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SnsQsProducer($this->createSnsContextMock(), $this->createSqsContextMock());
    }

    public function testShouldThrowIfMessageIsInvalidType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\SnsQs\SnsQsMessage but it is Double\Message\P4');

        $producer = new SnsQsProducer($this->createSnsContextMock(), $this->createSqsContextMock());

        $message = $this->prophesize(Message::class)->reveal();

        $producer->send(new SnsQsTopic(''), $message);
    }

    public function testShouldThrowIfDestinationOfInvalidType()
    {
        $this->expectException(InvalidDestinationException::class);

        $producer = new SnsQsProducer($this->createSnsContextMock(), $this->createSqsContextMock());

        $destination = $this->prophesize(Destination::class)->reveal();

        $producer->send($destination, new SnsQsMessage());
    }

    public function testShouldSetDeliveryDelayToSQSProducer()
    {
        $delay = 10;

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->setDeliveryDelay(Argument::is($delay))->shouldBeCalledTimes(1);

        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $producer = new SnsQsProducer($this->createSnsContextMock(), $sqsMock);

        $producer->setDeliveryDelay($delay);
    }

    public function testShouldGetDeliveryDelayFromSQSProducer()
    {
        $delay = 10;

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->getDeliveryDelay()->willReturn($delay);

        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $producer = new SnsQsProducer($this->createSnsContextMock(), $sqsMock);

        $this->assertEquals($delay, $producer->getDeliveryDelay());
    }

    public function testShouldSendSnsTopicMessageToSnsProducer()
    {
        $snsMock = $this->createSnsContextMock();
        $destination = new SnsQsTopic('');

        $snsProducerStub = $this->prophesize(SnsProducer::class);
        $snsProducerStub->send($destination, Argument::any())->shouldBeCalledOnce();

        $snsMock->method('createProducer')->willReturn($snsProducerStub->reveal());

        $producer = new SnsQsProducer($snsMock, $this->createSqsContextMock());
        $producer->send($destination, new SnsQsMessage());
    }

    public function testShouldSendSqsMessageToSqsProducer()
    {
        $sqsMock = $this->createSqsContextMock();
        $destination = new SnsQsQueue('');

        $snsProducerStub = $this->prophesize(SqsProducer::class);
        $snsProducerStub->send($destination, Argument::any())->shouldBeCalledOnce();

        $sqsMock->method('createProducer')->willReturn($snsProducerStub->reveal());

        $producer = new SnsQsProducer($this->createSnsContextMock(), $sqsMock);
        $producer->send($destination, new SnsQsMessage());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SnsContext
     */
    private function createSnsContextMock(): SnsContext
    {
        return $this->createMock(SnsContext::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SqsContext
     */
    private function createSqsContextMock(): SqsContext
    {
        return $this->createMock(SqsContext::class);
    }
}
