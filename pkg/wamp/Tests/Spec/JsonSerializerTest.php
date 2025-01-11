<?php

namespace Enqueue\Wamp\Tests\Spec;

use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Wamp\JsonSerializer;
use Enqueue\Wamp\Serializer;
use Enqueue\Wamp\WampMessage;
use PHPUnit\Framework\TestCase;

/**
 * @group Wamp
 */
class JsonSerializerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSerializerInterface()
    {
        $this->assertClassImplements(Serializer::class, JsonSerializer::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new JsonSerializer();
    }

    public function testShouldConvertMessageToJsonString()
    {
        $serializer = new JsonSerializer();

        $message = new WampMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $json = $serializer->toString($message);

        $this->assertSame('{"body":"theBody","properties":{"aProp":"aPropVal"},"headers":{"aHeader":"aHeaderVal"}}', $json);
    }

    public function testThrowIfFailedToEncodeMessageToJson()
    {
        $serializer = new JsonSerializer();

        $resource = fopen(__FILE__, 'r');

        //guard
        $this->assertIsResource($resource);

        $message = new WampMessage('theBody', ['aProp' => $resource]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The malformed json given.');
        $serializer->toString($message);
    }

    public function testShouldConvertJsonStringToMessage()
    {
        $serializer = new JsonSerializer();

        $message = $serializer->toMessage('{"body":"theBody","properties":{"aProp":"aPropVal"},"headers":{"aHeader":"aHeaderVal"}}');

        $this->assertInstanceOf(WampMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['aProp' => 'aPropVal'], $message->getProperties());
        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
    }

    public function testThrowIfFailedToDecodeJsonToMessage()
    {
        $serializer = new JsonSerializer();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The malformed json given.');
        $serializer->toMessage('{]');
    }
}
