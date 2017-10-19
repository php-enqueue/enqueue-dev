<?php

namespace Enqueue\Dbal\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Enqueue\Dbal\DbalConsumer;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
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

    public function testRejectShouldInsertNewMessageOnRequeue()
    {
        $expectedMessage = [
            'body' => 'theBody',
            'headers' => '[]',
            'properties' => '[]',
            'priority' => 0,
            'queue' => 'queue',
            'redelivered' => true,
        ];

        $dbal = $this->createConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('insert')
            ->with('tableName', $this->equalTo($expectedMessage))
            ->will($this->returnValue(1))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->will($this->returnValue($dbal))
        ;
        $context
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('tableName'))
        ;

        $message = new DbalMessage();
        $message->setBody('theBody');

        $consumer = new DbalConsumer($context, new DbalDestination('queue'));
        $consumer->reject($message, true);
    }

    public function testRejectShouldThrowIfMessageWasNotInserted()
    {
        $dbal = $this->createConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('insert')
            ->willReturn(0)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->will($this->returnValue($dbal))
        ;

        $message = new DbalMessage();
        $message->setBody('theBody');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected record was inserted but it is not. message:');

        $consumer = new DbalConsumer($context, new DbalDestination('queue'));
        $consumer->reject($message, true);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Statement
     */
    private function createStatementMock()
    {
        return $this->createMock(Statement::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalContext
     */
    private function createContextMock()
    {
        return $this->createMock(DbalContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueryBuilder
     */
    private function createQueryBuilderMock()
    {
        return $this->createMock(QueryBuilder::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractPlatform
     */
    private function createPlatformMock()
    {
        return $this->createMock(AbstractPlatform::class);
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
