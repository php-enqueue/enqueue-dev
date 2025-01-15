<?php

namespace Enqueue\Pheanstalk\Tests;

use Enqueue\Pheanstalk\PheanstalkConsumer;
use Enqueue\Pheanstalk\PheanstalkDestination;
use Enqueue\Pheanstalk\PheanstalkMessage;
use Enqueue\Test\ClassExtensionTrait;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\TestCase;

class PheanstalkConsumerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldReturnQueueSetInConstructor()
    {
        $destination = new PheanstalkDestination('aQueueName');

        $consumer = new PheanstalkConsumer(
            $destination,
            $this->createPheanstalkMock()
        );

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testShouldReceiveFromQueueAndReturnNullIfNoMessageInQueue()
    {
        $destination = new PheanstalkDestination('theQueueName');

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('reserveFromTube')
            ->with('theQueueName', 1)
            ->willReturn(null)
        ;

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $this->assertNull($consumer->receive(1000));
    }

    public function testShouldReceiveFromQueueAndReturnMessageIfMessageInQueue()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $message = new  PheanstalkMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $job = new Job('theJobId', json_encode($message));

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('reserveFromTube')
            ->with('theQueueName', 1)
            ->willReturn($job)
        ;

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $actualMessage = $consumer->receive(1000);

        $this->assertSame('theBody', $actualMessage->getBody());
        $this->assertSame(['foo' => 'fooVal'], $actualMessage->getProperties());
        $this->assertSame(['bar' => 'barVal'], $actualMessage->getHeaders());
        $this->assertSame($job, $actualMessage->getJob());
    }

    public function testShouldReceiveNoWaitFromQueueAndReturnNullIfNoMessageInQueue()
    {
        $destination = new PheanstalkDestination('theQueueName');

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('reserveFromTube')
            ->with('theQueueName', 0)
            ->willReturn(null)
        ;

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $this->assertNull($consumer->receiveNoWait());
    }

    public function testShouldReceiveNoWaitFromQueueAndReturnMessageIfMessageInQueue()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $message = new PheanstalkMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $job = new Job('theJobId', json_encode($message));

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('reserveFromTube')
            ->with('theQueueName', 0)
            ->willReturn($job)
        ;

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $actualMessage = $consumer->receiveNoWait();

        $this->assertSame('theBody', $actualMessage->getBody());
        $this->assertSame(['foo' => 'fooVal'], $actualMessage->getProperties());
        $this->assertSame(['bar' => 'barVal'], $actualMessage->getHeaders());
        $this->assertSame($job, $actualMessage->getJob());
    }

    public function testShouldAcknowledgeMessage()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $message = new PheanstalkMessage();

        $job = new Job('theJobId', json_encode($message));
        $message->setJob($job);

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('delete')
            ->with($job)
        ;

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $consumer->acknowledge($message);
    }

    public function testAcknowledgeShouldThrowExceptionIfMessageHasNoJob()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $pheanstalk = $this->createPheanstalkMock();

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message could not be acknowledged because it does not have job set.');

        $consumer->acknowledge(new PheanstalkMessage());
    }

    public function testShouldRejectMessage()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $message = new PheanstalkMessage();

        $job = new Job('theJobId', json_encode($message));
        $message->setJob($job);

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('delete')
            ->with($job)
        ;

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $consumer->reject($message);
    }

    public function testRejectShouldThrowExceptionIfMessageHasNoJob()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $pheanstalk = $this->createPheanstalkMock();

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message could not be rejected because it does not have job set.');

        $consumer->reject(new PheanstalkMessage());
    }

    public function testShouldRequeueMessage()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $message = new PheanstalkMessage();

        $job = new Job('theJobId', json_encode($message));
        $message->setJob($job);

        $pheanstalk = $this->createPheanstalkMock();
        $pheanstalk
            ->expects($this->once())
            ->method('release')
            ->with($job, Pheanstalk::DEFAULT_PRIORITY, Pheanstalk::DEFAULT_DELAY)
        ;
        $pheanstalk
            ->expects($this->never())
            ->method('delete')
        ;

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $consumer->reject($message, true);
    }

    public function testRequeueShouldThrowExceptionIfMessageHasNoJob()
    {
        $destination = new PheanstalkDestination('theQueueName');
        $pheanstalk = $this->createPheanstalkMock();

        $consumer = new PheanstalkConsumer($destination, $pheanstalk);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message could not be requeued because it does not have job set.');

        $consumer->reject(new PheanstalkMessage(), true);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Pheanstalk
     */
    private function createPheanstalkMock()
    {
        return $this->createMock(Pheanstalk::class);
    }
}
