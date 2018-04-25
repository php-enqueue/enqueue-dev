<?php

namespace Enqueue\Gps\Tests;

use Enqueue\Gps\GpsMessage;
use Google\Cloud\PubSub\Message;
use PHPUnit\Framework\TestCase;

class GpsMessageTest extends TestCase
{
    public function testCouldSetGetNativeMessage()
    {
        $message = new GpsMessage();
        $message->setNativeMessage($nativeMessage = new Message([], []));

        $this->assertSame($nativeMessage, $message->getNativeMessage());
    }

    public function testColdBeSerializedToJson()
    {
        $message = new GpsMessage('theBody', ['thePropFoo' => 'thePropFooVal'], ['theHeaderFoo' => 'theHeaderFooVal']);

        $this->assertEquals('{"body":"theBody","properties":{"thePropFoo":"thePropFooVal"},"headers":{"theHeaderFoo":"theHeaderFooVal"}}', json_encode($message));
    }

    public function testCouldBeUnserializedFromJson()
    {
        $message = new GpsMessage('theBody', ['thePropFoo' => 'thePropFooVal'], ['theHeaderFoo' => 'theHeaderFooVal']);

        $json = json_encode($message);

        //guard
        $this->assertNotEmpty($json);

        $unserializedMessage = GpsMessage::jsonUnserialize($json);

        $this->assertInstanceOf(GpsMessage::class, $unserializedMessage);
        $this->assertEquals($message, $unserializedMessage);
    }

    public function testThrowIfMalformedJsonGivenOnUnsterilizedFromJson()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');

        GpsMessage::jsonUnserialize('{]');
    }
}
