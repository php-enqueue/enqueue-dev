<?php

namespace Enqueue\NullTransporter\Tests;

use Enqueue\NullTransporter\NullMessage;
use Enqueue\NullTransporter\NullProducer;
use Enqueue\NullTransporter\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Producer;
use PHPUnit\Framework\TestCase;

class NullProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
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
