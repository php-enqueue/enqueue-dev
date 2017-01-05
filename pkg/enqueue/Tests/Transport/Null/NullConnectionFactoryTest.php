<?php

namespace Enqueue\Tests\Transport\Null;

use Enqueue\Psr\ConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullConnectionFactory;
use Enqueue\Transport\Null\NullContext;

class NullConnectionFactoryTest extends \PHPUnit_Framework_TestCase
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
