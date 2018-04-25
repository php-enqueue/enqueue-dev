<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpConnectionFactory;
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
        new AmqpConnectionFactory('amqp+lib:');
        new AmqpConnectionFactory('amqps+lib:');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "amqp+foo" is not supported. Could be one of "amqp", "amqps", "amqp+lib');
        new AmqpConnectionFactory('amqp+foo:');
    }
}
