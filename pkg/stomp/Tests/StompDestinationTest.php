<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;
use Enqueue\Stomp\StompDestination;
use Enqueue\Test\ClassExtensionTrait;

class StompDestinationTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsTopicAndQueueInterfaces()
    {
        $this->assertClassImplements(PsrTopic::class, StompDestination::class);
        $this->assertClassImplements(PsrQueue::class, StompDestination::class);
    }

    public function testShouldReturnDestinationStringWithRoutingKey()
    {
        $destination = new StompDestination();
        $destination->setType(StompDestination::TYPE_AMQ_QUEUE);
        $destination->setStompName('name');
        $destination->setRoutingKey('routing-key');

        $this->assertSame(StompDestination::TYPE_AMQ_QUEUE, $destination->getType());
        $this->assertSame('name', $destination->getStompName());
        $this->assertSame('routing-key', $destination->getRoutingKey());
        $this->assertSame('/amq/queue/name/routing-key', $destination->getQueueName());
    }

    public function testShouldReturnDestinationStringWithoutRoutingKey()
    {
        $destination = new StompDestination();
        $destination->setType(StompDestination::TYPE_TOPIC);
        $destination->setStompName('name');

        $this->assertSame(StompDestination::TYPE_TOPIC, $destination->getType());
        $this->assertSame('name', $destination->getStompName());
        $this->assertNull($destination->getRoutingKey());
        $this->assertSame('/topic/name', $destination->getQueueName());
    }

    public function testShouldThrowLogicExceptionIfNameIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Destination type or name is not set');

        $destination = new StompDestination();
        $destination->setType(StompDestination::TYPE_QUEUE);

        $destination->getQueueName();
    }

    public function testShouldThrowLogicExceptionIfTypeIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Destination type or name is not set');

        $destination = new StompDestination();
        $destination->setStompName('name');

        $destination->getQueueName();
    }

    public function testSetTypeShouldThrowLogicExceptionIfTypeIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid destination type: "invalid-type"');

        $destination = new StompDestination();
        $destination->setType('invalid-type');
    }
}
