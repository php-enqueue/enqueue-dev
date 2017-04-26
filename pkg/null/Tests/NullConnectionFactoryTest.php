<?php

namespace Enqueue\Null\Tests;

use Enqueue\Psr\PsrConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\NullContext;
use PHPUnit\Framework\TestCase;

class NullConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, NullConnectionFactory::class);
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
