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

    public function testShouldReceiveMessage()
    {
        $dbalMessage = [
            'id' => 'id',
            'body' => 'body',
            'headers' => '{"hkey":"hvalue"}',
            'properties' => '{"pkey":"pvalue"}',
            'priority' => 5,
            'queue' => 'queue',
            'redelivered' => true,
        ];

        $statement = $this->createStatementMock();
        $statement
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($dbalMessage))
        ;

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->exactly(2))
            ->method('orderBy')
            ->will($this->returnSelf())
        ;

        $platform = $this->createPlatformMock();

        $dbal = $this->createConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;
        $dbal
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement)
        ;
        $dbal
            ->expects($this->once())
            ->method('delete')
            ->willReturn(1)
        ;
        $dbal
            ->expects($this->once())
            ->method('commit')
        ;
        $dbal
            ->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->will($this->returnValue($dbal))
        ;
        $context
            ->expects($this->atLeastOnce())
            ->method('getTableName')
            ->will($this->returnValue('tableName'))
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new DbalMessage())
        ;

        $consumer = new DbalConsumer($context, new DbalDestination('queue'));
        $result = $consumer->receiveNoWait();

        $this->assertInstanceOf(DbalMessage::class, $result);
        $this->assertEquals('body', $result->getBody());
        $this->assertEquals(['hkey' => 'hvalue'], $result->getHeaders());
        $this->assertEquals(['pkey' => 'pvalue'], $result->getProperties());
        $this->assertTrue($result->isRedelivered());
        $this->assertEquals(5, $result->getPriority());
    }

    public function testShouldReturnNullIfThereIsNoNewMessage()
    {
        $statement = $this->createStatementMock();
        $statement
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue(null))
        ;

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->exactly(2))
            ->method('orderBy')
            ->will($this->returnSelf())
        ;

        $platform = $this->createPlatformMock();

        $dbal = $this->createConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;
        $dbal
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement)
        ;
        $dbal
            ->expects($this->never())
            ->method('delete')
            ->willReturn(1)
        ;
        $dbal
            ->expects($this->once())
            ->method('commit')
        ;
        $dbal
            ->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->will($this->returnValue($dbal))
        ;
        $context
            ->expects($this->atLeastOnce())
            ->method('getTableName')
            ->will($this->returnValue('tableName'))
        ;
        $context
            ->expects($this->never())
            ->method('createMessage')
            ->willReturn(new DbalMessage())
        ;

        $consumer = new DbalConsumer($context, new DbalDestination('queue'));
        $consumer->setPollingInterval(1000);
        $result = $consumer->receive(.000001);

        $this->assertEmpty($result);
    }

    public function testShouldThrowIfMessageWasNotRemoved()
    {
        $statement = $this->createStatementMock();
        $statement
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue(['id' => '2134']))
        ;

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf())
        ;
        $queryBuilder
            ->expects($this->exactly(2))
            ->method('orderBy')
            ->will($this->returnSelf())
        ;

        $platform = $this->createPlatformMock();

        $dbal = $this->createConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;
        $dbal
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement)
        ;
        $dbal
            ->expects($this->once())
            ->method('delete')
            ->willReturn(0)
        ;
        $dbal
            ->expects($this->never())
            ->method('commit')
        ;
        $dbal
            ->expects($this->once())
            ->method('rollBack')
        ;
        $dbal
            ->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->will($this->returnValue($dbal))
        ;
        $context
            ->expects($this->atLeastOnce())
            ->method('getTableName')
            ->will($this->returnValue('tableName'))
        ;
        $context
            ->expects($this->never())
            ->method('createMessage')
            ->willReturn(new DbalMessage())
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected record was removed but it is not. id: "2134"');

        $consumer = new DbalConsumer($context, new DbalDestination('queue'));
        $consumer->setPollingInterval(1000);
        $consumer->receive(.000001);
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
