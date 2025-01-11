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

    /**
     * @doesNotPerformAssertions
     */
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

    public function testMessagePriorityPrecedesPriority()
    {
        $message = new PheanstalkMessage('theBody');
        $message->setPriority(100);

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
            ->with('{"body":"theBody","properties":[],"headers":{"priority":100}}', 100, Pheanstalk::DEFAULT_DELAY, Pheanstalk::DEFAULT_TTR)
        ;

        $producer = new PheanstalkProducer($pheanstalk);
        $producer->setPriority(50);

        $producer->send(
            new PheanstalkDestination('theQueueName'),
            $message
        );
    }

    public function testAccessDeliveryDelayAsMilliseconds()
    {
        $producer = new PheanstalkProducer($this->createPheanstalkMock());
        $producer->setDeliveryDelay(5000);

        $this->assertEquals(5000, $producer->getDeliveryDelay());
    }

    public function testDeliveryDelayResolvesToSeconds()
    {
        $message = new PheanstalkMessage('theBody');

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
            ->with('{"body":"theBody","properties":[],"headers":[]}', Pheanstalk::DEFAULT_PRIORITY, 5, Pheanstalk::DEFAULT_TTR)
        ;

        $producer = new PheanstalkProducer($pheanstalk);
        $producer->setDeliveryDelay(5000);

        $producer->send(
            new PheanstalkDestination('theQueueName'),
            $message
        );
    }

    public function testMessageDelayPrecedesDeliveryDelay()
    {
        $message = new PheanstalkMessage('theBody');
        $message->setDelay(25);

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
            ->with('{"body":"theBody","properties":[],"headers":{"delay":25}}', Pheanstalk::DEFAULT_PRIORITY, 25, Pheanstalk::DEFAULT_TTR)
        ;

        $producer = new PheanstalkProducer($pheanstalk);
        $producer->setDeliveryDelay(1000);

        $producer->send(
            new PheanstalkDestination('theQueueName'),
            $message
        );
    }

    public function testAccessTimeToLiveAsMilliseconds()
    {
        $producer = new PheanstalkProducer($this->createPheanstalkMock());
        $producer->setTimeToLive(5000);

        $this->assertEquals(5000, $producer->getTimeToLive());
    }

    public function testTimeToLiveResolvesToSeconds()
    {
        $message = new PheanstalkMessage('theBody');

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
            ->with('{"body":"theBody","properties":[],"headers":[]}', Pheanstalk::DEFAULT_PRIORITY, Pheanstalk::DEFAULT_DELAY, 5)
        ;

        $producer = new PheanstalkProducer($pheanstalk);
        $producer->setTimeToLive(5000);

        $producer->send(
            new PheanstalkDestination('theQueueName'),
            $message
        );
    }

    public function testMessageTimeToRunPrecedesTimeToLive()
    {
        $message = new PheanstalkMessage('theBody');
        $message->setTimeToRun(25);

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
            ->with('{"body":"theBody","properties":[],"headers":{"ttr":25}}', Pheanstalk::DEFAULT_PRIORITY, Pheanstalk::DEFAULT_DELAY, 25)
        ;

        $producer = new PheanstalkProducer($pheanstalk);
        $producer->setTimeToLive(1000);

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
