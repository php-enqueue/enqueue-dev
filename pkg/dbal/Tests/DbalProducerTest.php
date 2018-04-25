<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\DbalProducer;
use Enqueue\Test\ClassExtensionTrait;
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalContext
     */
    private function createContextMock()
    {
        return $this->createMock(DbalContext::class);
    }
}

class NotSupportedDestination1 implements PsrDestination
{
}
