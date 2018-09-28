<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Gearman\GearmanMessage;
use Enqueue\Gearman\GearmanProducer;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use PHPUnit\Framework\TestCase;

class GearmanProducerTest extends TestCase
{
    use ClassExtensionTrait;
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    public function testCouldBeConstructedWithGearmanClientAsFirstArgument()
    {
        new GearmanProducer($this->createGearmanClientMock());
    }

    public function testThrowIfDestinationInvalid()
    {
        $producer = new GearmanProducer($this->createGearmanClientMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Gearman\GearmanDestination but got Enqueue\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new GearmanMessage());
    }

    public function testThrowIfMessageInvalid()
    {
        $producer = new GearmanProducer($this->createGearmanClientMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Gearman\GearmanMessage but it is Enqueue\Null\NullMessage.');
        $producer->send(new GearmanDestination('aQueue'), new NullMessage());
    }

    public function testShouldJsonEncodeMessageAndPutToExpectedTube()
    {
        $message = new GearmanMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $gearman = $this->createGearmanClientMock();
        $gearman
            ->expects($this->once())
            ->method('doBackground')
            ->with(
                'theQueueName',
                '{"body":"theBody","properties":{"foo":"fooVal"},"headers":{"bar":"barVal"}}'
            )
        ;
        $gearman
            ->expects($this->once())
            ->method('returnCode')
            ->willReturn(\GEARMAN_SUCCESS)
        ;

        $producer = new GearmanProducer($gearman);

        $producer->send(
            new GearmanDestination('theQueueName'),
            $message
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\GearmanClient
     */
    private function createGearmanClientMock()
    {
        return $this->createMock(\GearmanClient::class);
    }
}
