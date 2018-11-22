<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\JsonSerializer;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\Serializer;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group Redis
 */
class JsonSerializerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSerializerInterface()
    {
        $this->assertClassImplements(Serializer::class, JsonSerializer::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new JsonSerializer();
    }

    public function testShouldConvertMessageToJsonString()
    {
        $serializer = new JsonSerializer();

        $message = new RedisMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $json = $serializer->toString($message);

        $this->assertSame('{"body":"theBody","properties":{"aProp":"aPropVal"},"headers":{"aHeader":"aHeaderVal"}}', $json);
    }

    public function testThrowIfFailedToEncodeMessageToJson()
    {
        $serializer = new JsonSerializer();

        $resource = fopen(__FILE__, 'rb');

        //guard
        $this->assertInternalType('resource', $resource);

        $message = new RedisMessage('theBody', ['aProp' => $resource]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The malformed json given.');
        $serializer->toString($message);
    }

    public function testShouldConvertJsonStringToMessage()
    {
        $serializer = new JsonSerializer();

        $message = $serializer->toMessage('{"body":"theBody","properties":{"aProp":"aPropVal"},"headers":{"aHeader":"aHeaderVal"}}');

        $this->assertInstanceOf(RedisMessage::class, $message);

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
