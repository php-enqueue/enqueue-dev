<?php

namespace Enqueue\SnsQs\Tests;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsMessage;
use Enqueue\Sns\SnsProducer;
use Enqueue\SnsQs\SnsQsMessage;
use Enqueue\SnsQs\SnsQsProducer;
use Enqueue\SnsQs\SnsQsQueue;
use Enqueue\SnsQs\SnsQsTopic;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsMessage;
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
        $this->expectExceptionMessageMatches('/The message must be an instance of Enqueue\\\\SnsQs\\\\SnsQsMessage but it is Double\\\\Message\\\\P\d+/');

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
        $snsMock->method('createMessage')->willReturn(new SnsMessage());
        $destination = new SnsQsTopic('');

        $snsProducerStub = $this->prophesize(SnsProducer::class);
        $snsProducerStub->send($destination, Argument::any())->shouldBeCalledOnce();

        $snsMock->method('createProducer')->willReturn($snsProducerStub->reveal());

        $producer = new SnsQsProducer($snsMock, $this->createSqsContextMock());
        $producer->send($destination, new SnsQsMessage());
    }

    public function testShouldSendSnsTopicMessageWithAttributesToSnsProducer()
    {
        $snsMock = $this->createSnsContextMock();
        $snsMock->method('createMessage')->willReturn(new SnsMessage());
        $destination = new SnsQsTopic('');

        $snsProducerStub = $this->prophesize(SnsProducer::class);
        $snsProducerStub->send(
            $destination,
            Argument::that(function (SnsMessage $snsMessage) {
                return $snsMessage->getMessageAttributes() === ['foo' => 'bar'];
            })
        )->shouldBeCalledOnce();

        $snsMock->method('createProducer')->willReturn($snsProducerStub->reveal());

        $producer = new SnsQsProducer($snsMock, $this->createSqsContextMock());
        $producer->send($destination, new SnsQsMessage('', [], [], ['foo' => 'bar']));
    }

    public function testShouldSendToSnsTopicMessageWithGroupIdAndDeduplicationId()
    {
        $snsMock = $this->createSnsContextMock();
        $snsMock->method('createMessage')->willReturn(new SnsMessage());
        $destination = new SnsQsTopic('');

        $snsProducerStub = $this->prophesize(SnsProducer::class);
        $snsProducerStub->send(
            $destination,
            Argument::that(function (SnsMessage $snsMessage) {
                return 'group-id' === $snsMessage->getMessageGroupId()
                    && 'deduplication-id' === $snsMessage->getMessageDeduplicationId();
            })
        )->shouldBeCalledOnce();

        $snsMock->method('createProducer')->willReturn($snsProducerStub->reveal());

        $snsMessage = new SnsQsMessage();
        $snsMessage->setMessageGroupId('group-id');
        $snsMessage->setMessageDeduplicationId('deduplication-id');

        $producer = new SnsQsProducer($snsMock, $this->createSqsContextMock());
        $producer->send($destination, $snsMessage);
    }

    public function testShouldSendSqsMessageToSqsProducer()
    {
        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createMessage')->willReturn(new SqsMessage());
        $destination = new SnsQsQueue('');

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->send($destination, Argument::any())->shouldBeCalledOnce();

        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $producer = new SnsQsProducer($this->createSnsContextMock(), $sqsMock);
        $producer->send($destination, new SnsQsMessage());
    }

    public function testShouldSendToSqsProducerMessageWithGroupIdAndDeduplicationId()
    {
        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createMessage')->willReturn(new SqsMessage());
        $destination = new SnsQsQueue('');

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->send(
            $destination,
            Argument::that(function (SqsMessage $sqsMessage) {
                return 'group-id' === $sqsMessage->getMessageGroupId()
                    && 'deduplication-id' === $sqsMessage->getMessageDeduplicationId();
            })
        )->shouldBeCalledOnce();

        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $sqsMessage = new SnsQsMessage();
        $sqsMessage->setMessageGroupId('group-id');
        $sqsMessage->setMessageDeduplicationId('deduplication-id');

        $producer = new SnsQsProducer($this->createSnsContextMock(), $sqsMock);
        $producer->send($destination, $sqsMessage);
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
