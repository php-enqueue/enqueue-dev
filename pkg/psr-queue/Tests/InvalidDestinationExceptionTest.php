<?php

namespace Enqueue\Psr\Tests;

use Enqueue\Psr\Exception as ExceptionInterface;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrDestination;
use Enqueue\Test\ClassExtensionTrait;

class InvalidDestinationExceptionTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(ExceptionInterface::class, InvalidDestinationException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new InvalidDestinationException();
    }

    public function testThrowIfAssertDestinationInstanceOfNotSameAsExpected()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage(
            'The destination must be an instance of Enqueue\Psr\Tests\DestinationBar'.
            ' but got Enqueue\Psr\Tests\DestinationFoo.'
        );

        InvalidDestinationException::assertDestinationInstanceOf(new DestinationFoo(), DestinationBar::class);
    }

    public function testShouldDoNothingIfAssertDestinationInstanceOfSameAsExpected()
    {
        InvalidDestinationException::assertDestinationInstanceOf(new DestinationFoo(), DestinationFoo::class);
    }
}

class DestinationBar implements PsrDestination
{
}

class DestinationFoo implements PsrDestination
{
}
