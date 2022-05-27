<?php

namespace Enqueue\NullTransporter\Tests;

use Enqueue\NullTransporter\NullConnectionFactory;
use Enqueue\NullTransporter\NullContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class NullConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, NullConnectionFactory::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullConnectionFactory();
    }

    public function testShouldReturnNullContextOnCreateContextCall()
    {
        $factory = new NullConnectionFactory();

        $context = $factory->createContext();

        $this->assertInstanceOf(NullContext::class, $context);
    }
}
