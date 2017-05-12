<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Psr\PsrConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class AmqpConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, AmqpConnectionFactory::class);
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
