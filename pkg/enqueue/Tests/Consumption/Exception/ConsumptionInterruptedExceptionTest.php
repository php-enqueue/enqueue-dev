<?php
namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\Exception\ConsumptionInterruptedException;
use Enqueue\Consumption\Exception\ExceptionInterface;
use Enqueue\Test\ClassExtensionTrait;

class ConsumptionInterruptedExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(ExceptionInterface::class, ConsumptionInterruptedException::class);
    }

    public function testShouldExtendLogicException()
    {
        $this->assertClassExtends(\LogicException::class, ConsumptionInterruptedException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ConsumptionInterruptedException();
    }
}
