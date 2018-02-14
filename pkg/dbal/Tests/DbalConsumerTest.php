<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalConsumer;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\DbalProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

class DbalConsumerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, DbalConsumer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalConsumer($this->createContextMock(), new DbalDestination('queue'));
    }

    public function testShouldReturnInstanceOfDestination()
    {
        $destination = new DbalDestination('queue');

        $consumer = new DbalConsumer($this->createContextMock(), $destination);

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testCouldCallAcknowledgedMethod()
    {
        $consumer = new DbalConsumer($this->createContextMock(), new DbalDestination('queue'));
        $consumer->acknowledge(new DbalMessage());
    }

    public function testCouldSetAndGetPollingInterval()
    {
        $destination = new DbalDestination('queue');

        $consumer = new DbalConsumer($this->createContextMock(), $destination);
        $consumer->setPollingInterval(123456);

        $this->assertEquals(123456, $consumer->getPollingInterval());
    }

    public function testRejectShouldThrowIfInstanceOfMessageIsInvalid()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            'The message must be an instance of '.
            'Enqueue\Dbal\DbalMessage '.
            'but it is Enqueue\Dbal\Tests\InvalidMessage.'
        );

        $consumer = new DbalConsumer($this->createContextMock(), new DbalDestination('queue'));
        $consumer->reject(new InvalidMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $queue = new DbalDestination('queue');

        $message = new DbalMessage();
        $message->setBody('theBody');

        $context = $this->createContextMock();
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;

        $consumer = new DbalConsumer($context, $queue);

        $consumer->reject($message);
    }

    public function testRejectShouldReSendMessageToSameQueueOnRequeue()
    {
        $queue = new DbalDestination('queue');

        $message = new DbalMessage();
        $message->setBody('theBody');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($message))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($producerMock))
        ;

        $consumer = new DbalConsumer($context, $queue);

        $consumer->reject($message, true);
    }

    /**
     * @return DbalProducer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createProducerMock()
    {
        return $this->createMock(DbalProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalContext
     */
    private function createContextMock()
    {
        return $this->createMock(DbalContext::class);
    }
}

class InvalidMessage implements PsrMessage
{
    public function getBody()
    {
    }

    public function setBody($body)
    {
    }

    public function setProperties(array $properties)
    {
    }

    public function getProperties()
    {
    }

    public function setProperty($name, $value)
    {
    }

    public function getProperty($name, $default = null)
    {
    }

    public function setHeaders(array $headers)
    {
    }

    public function getHeaders()
    {
    }

    public function setHeader($name, $value)
    {
    }

    public function getHeader($name, $default = null)
    {
    }

    public function setRedelivered($redelivered)
    {
    }

    public function isRedelivered()
    {
    }

    public function setCorrelationId($correlationId)
    {
    }

    public function getCorrelationId()
    {
    }

    public function setMessageId($messageId)
    {
    }

    public function getMessageId()
    {
    }

    public function getTimestamp()
    {
    }

    public function setTimestamp($timestamp)
    {
    }

    public function setReplyTo($replyTo)
    {
    }

    public function getReplyTo()
    {
    }
}
