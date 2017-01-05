<?php

namespace Enqueue\Tests\Rpc;

use Enqueue\Psr\Consumer;
use Enqueue\Rpc\Promise;
use Enqueue\Transport\Null\NullMessage;

class PromiseTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithExpectedSetOfArguments()
    {
        new Promise($this->createPsrConsumerMock(), 'aCorrelationId', 2);
    }

    public function testShouldTimeoutIfNoResponseMessage()
    {
        $psrConsumerMock = $this->createPsrConsumerMock();
        $psrConsumerMock
            ->expects($this->atLeastOnce())
            ->method('receive')
            ->willReturn(null)
        ;

        $promise = new Promise($psrConsumerMock, 'aCorrelationId', 2);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Time outed without receiving reply message. Timeout: 2, CorrelationId: aCorrelationId');
        $promise->getMessage();
    }

    public function testShouldReturnReplyMessageIfCorrelationIdSame()
    {
        $correlationId = 'theCorrelationId';

        $replyMessage = new NullMessage();
        $replyMessage->setCorrelationId($correlationId);

        $psrConsumerMock = $this->createPsrConsumerMock();
        $psrConsumerMock
            ->expects($this->once())
            ->method('receive')
            ->willReturn($replyMessage)
        ;
        $psrConsumerMock
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($replyMessage))
        ;

        $promise = new Promise($psrConsumerMock, $correlationId, 2);

        $actualReplyMessage = $promise->getMessage();
        $this->assertSame($replyMessage, $actualReplyMessage);
    }

    public function testShouldReQueueIfCorrelationIdNotSame()
    {
        $correlationId = 'theCorrelationId';

        $anotherReplyMessage = new NullMessage();
        $anotherReplyMessage->setCorrelationId('theOtherCorrelationId');

        $replyMessage = new NullMessage();
        $replyMessage->setCorrelationId($correlationId);

        $psrConsumerMock = $this->createPsrConsumerMock();
        $psrConsumerMock
            ->expects($this->at(0))
            ->method('receive')
            ->willReturn($anotherReplyMessage)
        ;
        $psrConsumerMock
            ->expects($this->at(1))
            ->method('reject')
            ->with($this->identicalTo($anotherReplyMessage), true)
        ;
        $psrConsumerMock
            ->expects($this->at(2))
            ->method('receive')
            ->willReturn($replyMessage)
        ;
        $psrConsumerMock
            ->expects($this->at(3))
            ->method('acknowledge')
            ->with($this->identicalTo($replyMessage))
        ;

        $promise = new Promise($psrConsumerMock, $correlationId, 2);

        $actualReplyMessage = $promise->getMessage();
        $this->assertSame($replyMessage, $actualReplyMessage);
    }

    public function testShouldTrySeveralTimesToReceiveReplyMessage()
    {
        $correlationId = 'theCorrelationId';

        $anotherReplyMessage = new NullMessage();
        $anotherReplyMessage->setCorrelationId('theOtherCorrelationId');

        $replyMessage = new NullMessage();
        $replyMessage->setCorrelationId($correlationId);

        $psrConsumerMock = $this->createPsrConsumerMock();
        $psrConsumerMock
            ->expects($this->at(0))
            ->method('receive')
            ->willReturn(null)
        ;
        $psrConsumerMock
            ->expects($this->at(1))
            ->method('receive')
            ->willReturn(null)
        ;
        $psrConsumerMock
            ->expects($this->at(2))
            ->method('receive')
            ->willReturn($replyMessage)
        ;
        $psrConsumerMock
            ->expects($this->at(3))
            ->method('acknowledge')
            ->with($this->identicalTo($replyMessage))
        ;

        $promise = new Promise($psrConsumerMock, $correlationId, 2);

        $actualReplyMessage = $promise->getMessage();
        $this->assertSame($replyMessage, $actualReplyMessage);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Consumer
     */
    private function createPsrConsumerMock()
    {
        return $this->createMock(Consumer::class);
    }
}
