<?php

namespace Enqueue\Psr\Tests;

use Enqueue\Psr\Exception as ExceptionInterface;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Test\ClassExtensionTrait;

class InvalidMessageExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(ExceptionInterface::class, InvalidMessageException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new InvalidMessageException();
    }
}
