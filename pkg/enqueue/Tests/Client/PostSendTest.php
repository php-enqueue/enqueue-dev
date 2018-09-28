<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\PostSend;
use Enqueue\Client\ProducerInterface;
use Enqueue\Test\ClassExtensionTrait;
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
            $this->createDriverMock()
        );
    }

    public function testShouldAllowGetArgumentSetInConstructor()
    {
        $expectedMessage = new Message();
        $expectedProducer = $this->createProducerMock();
        $expectedDriver = $this->createDriverMock();

        $context = new PostSend(
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
        $message->setProperty(Config::COMMAND_PARAMETER, 'theCommand');

        $context = new PostSend(
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
        $message->setProperty(Config::TOPIC_PARAMETER, 'theTopic');

        $context = new PostSend(
            $message,
            $this->createProducerMock(),
            $this->createDriverMock()
        );

        $this->assertTrue($context->isEvent());
        $this->assertSame('theTopic', $context->getTopic());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createDriverMock(): DriverInterface
    {
        return $this->createMock(DriverInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createProducerMock(): ProducerInterface
    {
        return $this->createMock(ProducerInterface::class);
    }
}
