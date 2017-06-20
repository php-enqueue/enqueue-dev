<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testCouldBeConstructedWithExpectedArguments()
    {
        $result = new Result('theStatus');

        $this->assertSame('theStatus', $result->getStatus());
        $this->assertSame('', $result->getReason());
        $this->assertSame(null, $result->getReply());

        $result = new Result('theStatus', 'theReason');

        $this->assertSame('theStatus', $result->getStatus());
        $this->assertSame('theReason', $result->getReason());
        $this->assertSame(null, $result->getReply());
    }

    public function testCouldConstructedWithAckFactoryMethod()
    {
        $result = Result::ack('theReason');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::ACK, $result->getStatus());
        $this->assertSame('theReason', $result->getReason());
        $this->assertSame(null, $result->getReply());
    }

    public function testCouldConstructedWithRejectFactoryMethod()
    {
        $result = Result::reject('theReason');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::REJECT, $result->getStatus());
        $this->assertSame('theReason', $result->getReason());
        $this->assertSame(null, $result->getReply());
    }

    public function testCouldConstructedWithRequeueFactoryMethod()
    {
        $result = Result::requeue('theReason');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::REQUEUE, $result->getStatus());
        $this->assertSame('theReason', $result->getReason());
        $this->assertSame(null, $result->getReply());
    }

    public function testCouldConstructedWithReplyFactoryMethodAndAckStatusByDefault()
    {
        $reply = new NullMessage();

        $result = Result::reply($reply);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::ACK, $result->getStatus());
        $this->assertSame('', $result->getReason());
        $this->assertSame($reply, $result->getReply());
    }

    public function testCouldConstructedWithReplyFactoryMethodAndRejectStatusExplicitly()
    {
        $reply = new NullMessage();

        $result = Result::reply($reply, Result::REJECT);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::REJECT, $result->getStatus());
        $this->assertSame('', $result->getReason());
        $this->assertSame($reply, $result->getReply());
    }

    public function testCouldConstructedWithReplyFactoryMethodAndReasonSet()
    {
        $reply = new NullMessage();

        $result = Result::reply($reply, null, 'theReason');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::ACK, $result->getStatus());
        $this->assertSame('theReason', $result->getReason());
        $this->assertSame($reply, $result->getReply());
    }
}
