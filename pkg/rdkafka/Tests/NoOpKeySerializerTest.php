<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\NoOpKeySerializer;

/**
 * @group rdkafka
 */
class NoOpKeySerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideKeyData
     *
     * @param mixed $key
     */
    public function testItShouldReturnKeyAsIsInToString($key)
    {
        $noOp = new NoOpKeySerializer();
        $this->assertSame($key, $noOp->toString($key));
    }

    /**
     * @dataProvider provideKeyData
     *
     * @param mixed $key
     */
    public function testItShouldNotConvertInToKey($key)
    {
        $noOp = new NoOpKeySerializer();
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
