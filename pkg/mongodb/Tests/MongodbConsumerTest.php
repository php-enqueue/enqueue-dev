<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbConsumer;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Mongodb\MongodbMessage;
use Enqueue\Mongodb\MongodbProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

/**
 * @group mongodb
 */
class MongodbConsumerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, MongodbConsumer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new MongodbConsumer($this->createContextMock(), new MongodbDestination('queue'));
    }

    public function testShouldReturnInstanceOfDestination()
    {
        $destination = new MongodbDestination('queue');

        $consumer = new MongodbConsumer($this->createContextMock(), $destination);

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testCouldCallAcknowledgedMethod()
    {
        $consumer = new MongodbConsumer($this->createContextMock(), new MongodbDestination('queue'));
        $consumer->acknowledge(new MongodbMessage());
    }

    public function testCouldSetAndGetPollingInterval()
    {
        $destination = new MongodbDestination('queue');

        $consumer = new MongodbConsumer($this->createContextMock(), $destination);
        $consumer->setPollingInterval(123456);

        $this->assertEquals(123456, $consumer->getPollingInterval());
    }

    public function testRejectShouldThrowIfInstanceOfMessageIsInvalid()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            'The message must be an instance of '.
            'Enqueue\Mongodb\MongodbMessage '.
            'but it is Enqueue\Mongodb\Tests\InvalidMessage.'
        );

        $consumer = new MongodbConsumer($this->createContextMock(), new MongodbDestination('queue'));
        $consumer->reject(new InvalidMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $queue = new MongodbDestination('queue');

        $message = new MongodbMessage();
        $message->setBody('theBody');

        $context = $this->createContextMock();
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;

        $consumer = new MongodbConsumer($context, $queue);

        $consumer->reject($message);
    }

    public function testRejectShouldReSendMessageToSameQueueOnRequeue()
    {
        $queue = new MongodbDestination('queue');

        $message = new MongodbMessage();
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

        $consumer = new MongodbConsumer($context, $queue);

        $consumer->reject($message, true);
    }

    /**
     * @return MongodbProducer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createProducerMock()
    {
        return $this->createMock(MongodbProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MongodbContext
     */
    private function createContextMock()
    {
        return $this->createMock(MongodbContext::class);
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
