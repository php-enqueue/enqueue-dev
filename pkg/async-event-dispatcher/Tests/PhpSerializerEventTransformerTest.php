<?php

namespace Enqueue\AsyncEventDispatcher\Tests;

use Enqueue\AsyncEventDispatcher\EventTransformer;
use Enqueue\AsyncEventDispatcher\PhpSerializerEventTransformer;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class PhpSerializerEventTransformerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementEventTransformerInterface()
    {
        $this->assertClassImplements(EventTransformer::class, PhpSerializerEventTransformer::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new PhpSerializerEventTransformer($this->createContextStub());
    }

    public function testShouldReturnMessageWithPhpSerializedEventAsBodyOnToMessage()
    {
        $transformer = new PhpSerializerEventTransformer($this->createContextStub());

        $event = new GenericEvent('theSubject');
        $expectedBody = serialize($event);

        $message = $transformer->toMessage('fooEvent', $event);

        $this->assertInstanceOf(PsrMessage::class, $message);
        $this->assertEquals($expectedBody, $message->getBody());
    }

    public function testShouldReturnEventUnserializedFromMessageBodyOnToEvent()
    {
        $message = new NullMessage();
        $message->setBody(serialize(new GenericEvent('theSubject')));

        $transformer = new PhpSerializerEventTransformer($this->createContextStub());

        $event = $transformer->toEvent('anEventName', $message);

        $this->assertInstanceOf(GenericEvent::class, $event);
        $this->assertEquals('theSubject', $event->getSubject());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createContextStub()
    {
        $context = $this->createMock(PsrContext::class);
        $context
            ->expects($this->any())
            ->method('createMessage')
            ->willReturnCallback(function ($body) {
                return new NullMessage($body);
            })
        ;

        return $context;
    }
}
