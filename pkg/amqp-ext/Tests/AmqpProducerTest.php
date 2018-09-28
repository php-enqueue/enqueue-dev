<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Producer;
use PHPUnit\Framework\TestCase;

class AmqpProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, AmqpProducer::class);
    }
}
