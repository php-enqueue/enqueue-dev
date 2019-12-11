<?php

namespace Enqueue\Pheanstalk\Tests;

use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Pheanstalk\PheanstalkDestination;
use Enqueue\Pheanstalk\PheanstalkMessage;
use Enqueue\Pheanstalk\PheanstalkProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PheanstalkProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithPheanstalkAsFirstArgument()
    {
        new PheanstalkProducer($this->createPheanstalkMock());
    }

    public function testThrowIfDestinationInvalid()
    {
        $producer = new PheanstalkProducer($this->createPheanstalkMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Pheanstalk\PheanstalkDestination but got Enqueue\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new PheanstalkMessage());
    }

    public function testThrowIfMessageInvalid()
    {
        $producer = new PheanstalkProducer($this->createPheanstalkMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Pheanstalk\PheanstalkMessage but it is Enqueue\Null\NullMessage.');
        $producer->send(new PheanstalkDestination('aQueue'), new NullMessage());
    }

    public function testShouldJsonEncodeMessageAndPutToExpectedTube()
    {
        $message = new PheanstalkMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('useTube')
            ->with('theQueueName')
            ->willReturnSelf()
        ;
        $pheanstalk
            ->expects($this->once())
            ->method('put')
            ->with('{"body":"theBody","properties":{"foo":"fooVal"},"headers":{"bar":"barVal"}}')
        ;

        $producer = new PheanstalkProducer($pheanstalk);

        $producer->send(
            new PheanstalkDestination('theQueueName'),
            $message
        );
    }

    /**
     * @return MockObject|Pheanstalk
     */
    private function createPheanstalkMock()
    {
        return $this->createMock(Pheanstalk::class);
    }
}
