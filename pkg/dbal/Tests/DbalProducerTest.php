<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\DbalProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Producer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbalProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, DbalProducer::class);
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
     * @return MockObject|DbalContext
     */
    private function createContextMock()
    {
        return $this->createMock(DbalContext::class);
    }
}

class NotSupportedDestination1 implements Destination
{
}
