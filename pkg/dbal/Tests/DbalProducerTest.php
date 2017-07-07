<?php

namespace Enqueue\Dbal\Tests;

use Doctrine\DBAL\Connection;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\DbalProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrProducer;

class DbalProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, DbalProducer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalProducer($this->createContextMock());
    }

    public function testShouldThrowIfBodyOfInvalidType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message body must be a scalar or null. Got: stdClass');

        $producer = new DbalProducer($this->createContextMock());

        $message = new DbalMessage(new \stdClass());

        $producer->send(new DbalDestination(''), $message);
    }

    public function testShouldThrowIfDestinationOfInvalidType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage(
            'The destination must be an instance of '.
            'Enqueue\Dbal\DbalDestination but got '.
            'Enqueue\Dbal\Tests\NotSupportedDestination1.'
        );

        $producer = new DbalProducer($this->createContextMock());

        $producer->send(new NotSupportedDestination1(), new DbalMessage());
    }

    public function testShouldThrowIfInsertMessageFailed()
    {
        $dbal = $this->createConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('insert')
            ->will($this->throwException(new \Exception('error message')))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->will($this->returnValue($dbal))
        ;

        $destination = new DbalDestination('queue-name');
        $message = new DbalMessage();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The transport fails to send the message due to some internal error.');

        $producer = new DbalProducer($context);
        $producer->send($destination, $message);
    }

    public function testShouldSendMessage()
    {
        $expectedMessage = [
            'body' => 'body',
            'headers' => '{"hkey":"hvalue"}',
            'properties' => '{"pkey":"pvalue"}',
            'priority' => 123,
            'queue' => 'queue-name',
        ];

        $dbal = $this->createConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('insert')
            ->with('tableName', $expectedMessage)
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

        $destination = new DbalDestination('queue-name');
        $message = new DbalMessage();
        $message->setBody('body');
        $message->setHeaders(['hkey' => 'hvalue']);
        $message->setProperties(['pkey' => 'pvalue']);
        $message->setPriority(123);

        $producer = new DbalProducer($context);
        $producer->send($destination, $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalContext
     */
    private function createContextMock()
    {
        return $this->createMock(DbalContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }
}

class NotSupportedDestination1 implements PsrDestination
{
}
