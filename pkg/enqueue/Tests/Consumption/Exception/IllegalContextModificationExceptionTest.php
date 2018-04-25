<?php

namespace Enqueue\Tests\Consumption\Exception;

use Enqueue\Consumption\Exception\ExceptionInterface;
use Enqueue\Consumption\Exception\IllegalContextModificationException;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class IllegalContextModificationExceptionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(ExceptionInterface::class, IllegalContextModificationException::class);
    }

    public function testShouldExtendLogicException()
    {
        $this->assertClassExtends(\LogicException::class, IllegalContextModificationException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new IllegalContextModificationException();
    }
}
