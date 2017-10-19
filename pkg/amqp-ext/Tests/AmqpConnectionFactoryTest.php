<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
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

    public function testShouldSupportAmqpExtScheme()
    {
        // no exception here
        new AmqpConnectionFactory('amqp+ext:');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "amqp+foo" is not supported. Could be one of "amqp", "amqp+ext" only.');
        new AmqpConnectionFactory('amqp+foo:');
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new AmqpConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(AmqpContext::class, $context);

        $this->assertAttributeEquals(null, 'extChannel', $context);
        $this->assertInternalType('callable', $this->readAttribute($context, 'extChannelFactory'));
    }
}
