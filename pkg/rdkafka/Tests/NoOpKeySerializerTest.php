<?php

namespace Enqueue\RdKafka\Tests;
use Enqueue\RdKafka\NoOpKeySerializer;

/**
 * @group rdkafka
 */
class NoOpKeySerializerTest extends \PHPUnit_Framework_TestCase
{
    public function testItShouldReturnKeyAsIsInToString()
    {
        $noOp = new NoOpKeySerializer();

        $key = 'key';
        $this->assertSame($key, $noOp->toString($key));

        $key = 40;
        $this->assertSame($key, $noOp->toString($key));

        $key = ['key' => 'test'];
        $this->assertSame($key, $noOp->toString($key));
    }
}
