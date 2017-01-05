<?php

namespace Enqueue\Tests\Transport\Null;

use Enqueue\Psr\Producer;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullMessage;
use Enqueue\Transport\Null\NullProducer;
use Enqueue\Transport\Null\NullTopic;

class NullProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(Producer::class, NullProducer::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullProducer();
    }

    public function testShouldDoNothingOnSend()
    {
        $producer = new NullProducer();

        $producer->send(new NullTopic('aName'), new NullMessage());
    }
}
