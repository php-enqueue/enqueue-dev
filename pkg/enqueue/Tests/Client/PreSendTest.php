<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\PreSend;
use Enqueue\Client\ProducerInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class PreSendTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeFinal()
    {
        self::assertClassFinal(PreSend::class);
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new PreSend(
            'aCommandOrTopic',
            new Message(),
            $this->createProducerMock(),
            $this->createDriverMock()
        );
    }

    public function testShouldAllowGetArgumentSetInConstructor()
    {
        $expectedCommandOrTopic = 'theCommandOrTopic';
        $expectedMessage = new Message();
        $expectedProducer = $this->createProducerMock();
        $expectedDriver = $this->createDriverMock();

        $context = new PreSend(
            $expectedCommandOrTopic,
            $expectedMessage,
            $expectedProducer,
            $expectedDriver
        );

        $this->assertSame($expectedCommandOrTopic, $context->getTopic());
        $this->assertSame($expectedCommandOrTopic, $context->getCommand());
        $this->assertSame($expectedMessage, $context->getMessage());
        $this->assertSame($expectedProducer, $context->getProducer());
        $this->assertSame($expectedDriver, $context->getDriver());

        $this->assertEquals($expectedMessage, $context->getOriginalMessage());
        $this->assertNotSame($expectedMessage, $context->getOriginalMessage());
    }

    public function testCouldChangeTopic()
    {
        $context = new PreSend(
            'aCommandOrTopic',
            new Message(),
            $this->createProducerMock(),
            $this->createDriverMock()
        );

        //guard
        $this->assertSame('aCommandOrTopic', $context->getTopic());

        $context->changeTopic('theChangedTopic');

        $this->assertSame('theChangedTopic', $context->getTopic());
    }

    public function testCouldChangeCommand()
    {
        $context = new PreSend(
            'aCommandOrTopic',
            new Message(),
            $this->createProducerMock(),
            $this->createDriverMock()
        );

        //guard
        $this->assertSame('aCommandOrTopic', $context->getCommand());

        $context->changeCommand('theChangedCommand');

        $this->assertSame('theChangedCommand', $context->getCommand());
    }

    public function testCouldChangeBody()
    {
        $context = new PreSend(
            'aCommandOrTopic',
            new Message('aBody'),
            $this->createProducerMock(),
            $this->createDriverMock()
        );

        //guard
        $this->assertSame('aBody', $context->getMessage()->getBody());
        $this->assertNull($context->getMessage()->getContentType());

        $context->changeBody('theChangedBody');
        $this->assertSame('theChangedBody', $context->getMessage()->getBody());
        $this->assertNull($context->getMessage()->getContentType());

        $context->changeBody('theChangedBodyAgain', 'foo/bar');
        $this->assertSame('theChangedBodyAgain', $context->getMessage()->getBody());
        $this->assertSame('foo/bar', $context->getMessage()->getContentType());
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
