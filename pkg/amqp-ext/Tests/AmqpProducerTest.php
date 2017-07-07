<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrProducer;
use PHPUnit\Framework\TestCase;

class AmqpProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, AmqpProducer::class);
    }
}
