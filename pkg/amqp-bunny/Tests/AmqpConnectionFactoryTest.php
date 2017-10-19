<?php

namespace Enqueue\AmqpBunny\Tests;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConnectionFactory;
use PHPUnit\Framework\TestCase;

class AmqpConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, AmqpConnectionFactory::class);
    }

    public function testShouldSupportAmqpLibScheme()
    {
        // no exception here
        new AmqpConnectionFactory('amqp+bunny:');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "amqp+foo" is not supported. Could be one of "amqp", "amqp+bunny" only.');
        new AmqpConnectionFactory('amqp+foo:');
    }
}
