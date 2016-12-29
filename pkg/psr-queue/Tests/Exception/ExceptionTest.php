<?php
namespace Enqueue\Psr\Tests\Exception;

use Enqueue\Psr\Exception;
use Enqueue\Psr\ExceptionInterface;
use Enqueue\Test\ClassExtensionTrait;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(\Exception::class, Exception::class);
    }

    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(ExceptionInterface::class, Exception::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new Exception();
    }
}
