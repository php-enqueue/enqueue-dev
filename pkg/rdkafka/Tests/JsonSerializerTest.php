<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\JsonSerializer;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\Serializer;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group rdkafka
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

        $message = new RdKafkaMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $json = $serializer->toString($message);

        $this->assertSame('{"body":"theBody","properties":{"aProp":"aPropVal"},"headers":{"aHeader":"aHeaderVal"}}', $json);
    }

    public function testThrowIfFailedToEncodeMessageToJson()
    {
        $serializer = new JsonSerializer();

        $resource = fopen(__FILE__, 'r');

        //guard
        $this->assertInternalType('resource', $resource);

        $message = new RdKafkaMessage('theBody', ['aProp' => $resource]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Could not encode value into json.');
        $serializer->toString($message);
    }

    public function testShouldConvertJsonStringToMessage()
    {
        $serializer = new JsonSerializer();

        $message = $serializer->toMessage('{"body":"theBody","properties":{"aProp":"aPropVal"},"headers":{"aHeader":"aHeaderVal"}}');

        $this->assertInstanceOf(RdKafkaMessage::class, $message);

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
