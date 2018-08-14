<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Mongodb\MongodbMessage;
use Enqueue\Mongodb\MongodbProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrProducer;

/**
 * @group mongodb
 */
class MongodbProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, MongodbProducer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new MongodbProducer($this->createContextMock());
    }

    public function testShouldThrowIfBodyOfInvalidType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message body must be a scalar or null. Got: stdClass');

        $producer = new MongodbProducer($this->createContextMock());

        $message = new MongodbMessage(new \stdClass());

        $producer->send(new MongodbDestination(''), $message);
    }

    public function testShouldThrowIfDestinationOfInvalidType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage(
            'The destination must be an instance of '.
            'Enqueue\Mongodb\MongodbDestination but got '.
            'Enqueue\Mongodb\Tests\NotSupportedDestination1.'
        );

        $producer = new MongodbProducer($this->createContextMock());

        $producer->send(new NotSupportedDestination1(), new MongodbMessage());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MongodbContext
     */
    private function createContextMock()
    {
        return $this->createMock(MongodbContext::class);
    }
}

class NotSupportedDestination1 implements PsrDestination
{
}
