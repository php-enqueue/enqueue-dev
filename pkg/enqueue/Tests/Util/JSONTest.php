<?php

namespace Enqueue\Tests\Util;

use Enqueue\Tests\Util\Fixtures\JsonSerializableClass;
use Enqueue\Tests\Util\Fixtures\SimpleClass;
use Enqueue\Util\JSON;
use PHPUnit\Framework\TestCase;

class JSONTest extends TestCase
{
    public function testShouldDecodeString()
    {
        $this->assertSame(['foo' => 'fooVal'], JSON::decode('{"foo": "fooVal"}'));
    }

    public function testThrowIfMalformedJson()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'The malformed json given. ');
        $this->assertSame(['foo' => 'fooVal'], JSON::decode('{]'));
    }

    public function nonStringDataProvider()
    {
        $resource = fopen('php://memory', 'r');
        fclose($resource);

        return [
            [null],
            [true],
            [false],
            [new \stdClass()],
            [123],
            [123.45],
            [$resource],
        ];
    }

    /**
     * @dataProvider nonStringDataProvider
     *
     * @param mixed $value
     */
    public function testShouldThrowExceptionIfInputIsNotString($value)
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Accept only string argument but got:'
        );

        $this->assertSame(0, JSON::decode($value));
    }

    public function testShouldReturnNullIfInputStringIsEmpty()
    {
        $this->assertNull(JSON::decode(''));
    }

    public function testShouldEncodeArray()
    {
        $this->assertEquals('{"key":"value"}', JSON::encode(['key' => 'value']));
    }

    public function testShouldEncodeString()
    {
        $this->assertEquals('"string"', JSON::encode('string'));
    }

    public function testShouldEncodeNumeric()
    {
        $this->assertEquals('123.45', JSON::encode(123.45));
    }

    public function testShouldEncodeNull()
    {
        $this->assertEquals('null', JSON::encode(null));
    }

    public function testShouldEncodeObjectOfStdClass()
    {
        $obj = new \stdClass();
        $obj->key = 'value';

        $this->assertEquals('{"key":"value"}', JSON::encode($obj));
    }

    public function testShouldEncodeObjectOfSimpleClass()
    {
        $this->assertEquals('{"keyPublic":"public"}', JSON::encode(new SimpleClass()));
    }

    public function testShouldEncodeObjectOfJsonSerializableClass()
    {
        $this->assertEquals('{"key":"value"}', JSON::encode(new JsonSerializableClass()));
    }

    public function testThrowIfValueIsResource()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Could not encode value into json. Error 8 and message Type is not supported'
        );

        $resource = fopen('php://memory', 'r');
        fclose($resource);

        JSON::encode($resource);
    }
}
