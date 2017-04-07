<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\Psr\PsrProducer;
use Enqueue\Test\ClassExtensionTrait;

class AmqpProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, AmqpProducer::class);
    }
}
