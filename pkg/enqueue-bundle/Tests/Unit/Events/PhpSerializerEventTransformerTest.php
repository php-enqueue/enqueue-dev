<?php

namespace Enqueue\Bundle\Tests\Unit\Events;

use Enqueue\Bundle\Events\EventTransformer;
use Enqueue\Bundle\Events\PhpSerializerEventTransformer;
use Enqueue\Client\Message;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Kernel;

class PhpSerializerEventTransformerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementEventTransformerInterface()
    {
        $this->assertClassImplements(EventTransformer::class, PhpSerializerEventTransformer::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new PhpSerializerEventTransformer();
    }

    public function testShouldReturnMessageWithPhpSerializedEventAsBodyOnToMessage()
    {
        if (version_compare(Kernel::VERSION, '3.0', '<')) {
            $this->markTestSkipped('This functionality only works on Symfony 3.0 or higher');
        }

        $transformer = new PhpSerializerEventTransformer();

        $event = new GenericEvent('theSubject');
        $expectedBody = serialize($event);

        $message = $transformer->toMessage('fooEvent', $event);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($expectedBody, $message->getBody());
    }

    public function testShouldReturnEventUnserializedFromMessageBodyOnToEvent()
    {
        if (version_compare(Kernel::VERSION, '3.0', '<')) {
            $this->markTestSkipped('This functionality only works on Symfony 3.0 or higher');
        }

        $message = new NullMessage();
        $message->setBody(serialize(new GenericEvent('theSubject')));

        $transformer = new PhpSerializerEventTransformer();

        $event = $transformer->toEvent('anEventName', $message);

        $this->assertInstanceOf(GenericEvent::class, $event);
        $this->assertEquals('theSubject', $event->getSubject());
    }

    public function testThrowNotSupportedExceptionOnSymfonyPrior30OnToMessage()
    {
        if (version_compare(Kernel::VERSION, '3.0', '>=')) {
            $this->markTestSkipped('This functionality only works on Symfony 3.0 or higher');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This transformer does not work on Symfony prior 3.0.');

        $transformer = new PhpSerializerEventTransformer();

        $transformer->toMessage(new GenericEvent());
    }

    public function testThrowNotSupportedExceptionOnSymfonyPrior30OnToEvent()
    {
        if (version_compare(Kernel::VERSION, '3.0', '>=')) {
            $this->markTestSkipped('This functionality only works on Symfony 3.0 or higher');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This transformer does not work on Symfony prior 3.0.');

        $transformer = new PhpSerializerEventTransformer();

        $transformer->toEvent('anEvent', new NullMessage());
    }
}
