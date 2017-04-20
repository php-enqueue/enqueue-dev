<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\StompHeadersEncoder;

class StompHeadersEncoderTest extends \PHPUnit\Framework\TestCase
{
    public function headerValuesDataProvider()
    {
        return [
            [['key' => 'Lorem ipsum'], ['key' => 'Lorem ipsum', '_type_key' => 's']],
            [['key' => 1234], ['key' => '1234', '_type_key' => 'i']],
            [['key' => 123.45], ['key' => '123.45', '_type_key' => 'f']],
            [['key' => true], ['key' => 'true', '_type_key' => 'b']],
            [['key' => false], ['key' => 'false', '_type_key' => 'b']],
            [['key' => null], ['key' => '', '_type_key' => 'n']],
        ];
    }

    public function propertyValuesDataProvider()
    {
        return [
            [['key' => 'Lorem ipsum'], ['_property_key' => 'Lorem ipsum', '_property__type_key' => 's']],
            [['key' => 1234], ['_property_key' => '1234', '_property__type_key' => 'i']],
            [['key' => 123.45], ['_property_key' => '123.45', '_property__type_key' => 'f']],
            [['key' => true], ['_property_key' => 'true', '_property__type_key' => 'b']],
            [['key' => false], ['_property_key' => 'false', '_property__type_key' => 'b']],
            [['key' => null], ['_property_key' => '', '_property__type_key' => 'n']],
        ];
    }

    /**
     * @dataProvider headerValuesDataProvider
     *
     * @param mixed $originalValue
     * @param mixed $encodedValue
     */
    public function testShouldEncodeHeaders($originalValue, $encodedValue)
    {
        $this->assertSame($encodedValue, StompHeadersEncoder::encode($originalValue));
    }

    /**
     * @dataProvider propertyValuesDataProvider
     *
     * @param mixed $originalValue
     * @param mixed $encodedValue
     */
    public function testShouldEncodeProperties($originalValue, $encodedValue)
    {
        $this->assertSame($encodedValue, StompHeadersEncoder::encode([], $originalValue));
    }

    /**
     * @dataProvider headerValuesDataProvider
     *
     * @param mixed $originalValue
     * @param mixed $encodedValue
     */
    public function testShouldDecodeHeaders($originalValue, $encodedValue)
    {
        $this->assertSame([$originalValue, []], StompHeadersEncoder::decode($encodedValue));
    }

    /**
     * @dataProvider propertyValuesDataProvider
     *
     * @param mixed $originalValue
     * @param mixed $encodedValue
     */
    public function testShouldDecodeProperties($originalValue, $encodedValue)
    {
        $this->assertSame([[], $originalValue], StompHeadersEncoder::decode($encodedValue));
    }

    public function testShouldKeepTypeAsIsIfHereIsNoTypeField()
    {
        $this->assertSame([['key' => 123.45], []], StompHeadersEncoder::decode(['key' => 123.45]));
    }
}
