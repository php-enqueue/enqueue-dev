<?php

namespace Enqueue\AsyncEventDispatcher\Tests;

use Enqueue\AsyncEventDispatcher\EventTransformer;
use Enqueue\AsyncEventDispatcher\PhpSerializerEventTransformer;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message;
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

        $this->assertInstanceOf(Message::class, $message);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    private function createContextStub()
    {
        $context = $this->createMock(Context::class);
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
