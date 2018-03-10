<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\DefaultKeySerializer;

/**
 * @group rdkafka
 */
class DefaultKeySerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideKeyData
     *
     * @param mixed $key
     */
    public function testItShouldReturnKeyAsIsInToString($key)
    {
        $noOp = new DefaultKeySerializer();
        $this->assertSame($key, $noOp->toString($key));
    }

    /**
     * @dataProvider provideKeyData
     *
     * @param mixed $key
     */
    public function testItShouldNotConvertInToKey($key)
    {
        $noOp = new DefaultKeySerializer();
        $this->assertSame($key, $noOp->toString($key));
    }

    public function provideKeyData()
    {
        yield [
            'string key' => 'key',
        ];

        yield [
            'int key' => 40,
        ];

        yield [
            'array key' => ['key' => 'test']
        ];
    }
}
