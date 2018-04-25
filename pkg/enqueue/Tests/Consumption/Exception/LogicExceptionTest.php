<?php

namespace Enqueue\Tests\Consumption\Exception;

use Enqueue\Consumption\Exception\ExceptionInterface;
use Enqueue\Consumption\Exception\LogicException;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class LogicExceptionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(ExceptionInterface::class, LogicException::class);
    }

    public function testShouldExtendLogicException()
    {
        $this->assertClassExtends(\LogicException::class, LogicException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new LogicException();
    }
}
