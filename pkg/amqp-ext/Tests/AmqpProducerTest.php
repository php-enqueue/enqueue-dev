<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\Psr\Producer;
use Enqueue\Test\ClassExtensionTrait;

class AmqpProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, AmqpProducer::class);
    }
}
