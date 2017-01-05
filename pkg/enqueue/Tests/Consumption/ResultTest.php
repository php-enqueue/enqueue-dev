<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\Result;
use Enqueue\Transport\Null\NullMessage;

class ResultTest extends \PHPUnit_Framework_TestCase
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

    public function testCouldConstructedWithReplyFactoryMethod()
    {
        $reply = new NullMessage();

        $result = Result::reply($reply, 'theReason');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::ACK, $result->getStatus());
        $this->assertSame('theReason', $result->getReason());
        $this->assertSame($reply, $result->getReply());
    }
}
