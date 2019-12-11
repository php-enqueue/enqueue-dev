<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\PostSend;
use Enqueue\Client\ProducerInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Message as TransportMessage;
use PHPUnit\Framework\TestCase;

class PostSendTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeFinal()
    {
        self::assertClassFinal(PostSend::class);
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new PostSend(
            new Message(),
            $this->createProducerMock(),
            $this->createDriverMock(),
            $this->createDestinationMock(),
            $this->createTransportMessageMock()
        );
    }

    public function testShouldAllowGetArgumentSetInConstructor()
    {
        $expectedMessage = new Message();
        $expectedProducer = $this->createProducerMock();
        $expectedDriver = $this->createDriverMock();
        $expectedDestination = $this->createDestinationMock();
        $expectedTransportMessage = $this->createTransportMessageMock();

        $context = new PostSend(
            $expectedMessage,
            $expectedProducer,
            $expectedDriver,
            $expectedDestination,
            $expectedTransportMessage
        );

        $this->assertSame($expectedMessage, $context->getMessage());
        $this->assertSame($expectedProducer, $context->getProducer());
        $this->assertSame($expectedDriver, $context->getDriver());
        $this->assertSame($expectedDestination, $context->getTransportDestination());
        $this->assertSame($expectedTransportMessage, $context->getTransportMessage());
    }

    public function testShouldAllowGetCommand()
    {
        $message = new Message();
        $message->setProperty(Config::COMMAND, 'theCommand');

        $context = new PostSend(
            $message,
            $this->createProducerMock(),
            $this->createDriverMock(),
            $this->createDestinationMock(),
            $this->createTransportMessageMock()
        );

        $this->assertFalse($context->isEvent());
        $this->assertSame('theCommand', $context->getCommand());
    }

    public function testShouldAllowGetTopic()
    {
        $message = new Message();
        $message->setProperty(Config::TOPIC, 'theTopic');

        $context = new PostSend(
            $message,
            $this->createProducerMock(),
            $this->createDriverMock(),
            $this->createDestinationMock(),
            $this->createTransportMessageMock()
        );

        $this->assertTrue($context->isEvent());
        $this->assertSame('theTopic', $context->getTopic());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createDriverMock(): DriverInterface
    {
        return $this->createMock(DriverInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createProducerMock(): ProducerInterface
    {
        return $this->createMock(ProducerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Destination
     */
    private function createDestinationMock(): Destination
    {
        return $this->createMock(Destination::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TransportMessage
     */
    private function createTransportMessageMock(): TransportMessage
    {
        return $this->createMock(TransportMessage::class);
    }
}
