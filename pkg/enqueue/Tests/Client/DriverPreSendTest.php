<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\DriverPreSend;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class DriverPreSendTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeFinal()
    {
        self::assertClassFinal(DriverPreSend::class);
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new DriverPreSend(
            new Message(),
            $this->createProducerMock(),
            $this->createDriverMock()
        );
    }

    public function testShouldAllowGetArgumentSetInConstructor()
    {
        $expectedMessage = new Message();
        $expectedProducer = $this->createProducerMock();
        $expectedDriver = $this->createDriverMock();

        $context = new DriverPreSend(
            $expectedMessage,
            $expectedProducer,
            $expectedDriver
        );

        $this->assertSame($expectedMessage, $context->getMessage());
        $this->assertSame($expectedProducer, $context->getProducer());
        $this->assertSame($expectedDriver, $context->getDriver());
    }

    public function testShouldAllowGetCommand()
    {
        $message = new Message();
        $message->setProperty(Config::COMMAND, 'theCommand');

        $context = new DriverPreSend(
            $message,
            $this->createProducerMock(),
            $this->createDriverMock()
        );

        $this->assertFalse($context->isEvent());
        $this->assertSame('theCommand', $context->getCommand());
    }

    public function testShouldAllowGetTopic()
    {
        $message = new Message();
        $message->setProperty(Config::TOPIC, 'theTopic');

        $context = new DriverPreSend(
            $message,
            $this->createProducerMock(),
            $this->createDriverMock()
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
}
