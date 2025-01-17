<?php

namespace Enqueue\Null\Tests;

use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\NullContext;
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

    public function testShouldReturnNullContextOnCreateContextCall()
    {
        $factory = new NullConnectionFactory();

        $context = $factory->createContext();

        $this->assertInstanceOf(NullContext::class, $context);
    }
}
