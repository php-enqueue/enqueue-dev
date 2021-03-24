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

    public function testMessageEntityCouldBeUnserializedFromJson()
    {
        $json = '{"body":"theBody","properties":{"thePropFoo":"thePropFooVal"},"headers":{"theHeaderFoo":"theHeaderFooVal"}}';

        $unserializedMessage = GpsMessage::jsonUnserialize($json);

        $this->assertInstanceOf(GpsMessage::class, $unserializedMessage);
        $decoded = json_decode($json, true);
        $this->assertEquals($decoded['body'], $unserializedMessage->getBody());
        $this->assertEquals($decoded['properties'], $unserializedMessage->getProperties());
        $this->assertEquals($decoded['headers'], $unserializedMessage->getHeaders());
    }

    public function testMessagePayloadCouldBeUnserializedFromJson()
    {
        $json = '{"theBodyPropFoo":"theBodyPropVal"}';

        $unserializedMessage = GpsMessage::jsonUnserialize($json);

        $this->assertInstanceOf(GpsMessage::class, $unserializedMessage);
        $this->assertEquals($json, $unserializedMessage->getBody());
        $this->assertEquals([], $unserializedMessage->getProperties());
        $this->assertEquals([], $unserializedMessage->getHeaders());
    }

    public function testThrowIfMalformedJsonGivenOnUnsterilizedFromJson()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');

        GpsMessage::jsonUnserialize('{]');
    }
}
