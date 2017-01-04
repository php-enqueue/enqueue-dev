<?php
namespace Enqueue\Psr\Tests;

use Enqueue\Psr\DeliveryMode;
use Enqueue\Psr\ExceptionInterface;
use Enqueue\Psr\InvalidDeliveryModeException;
use Enqueue\Test\ClassExtensionTrait;

class InvalidDeliveryModeExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(ExceptionInterface::class, InvalidDeliveryModeException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new InvalidDeliveryModeException();
    }

    public function testThrowIfDeliveryModeIsNotValid()
    {
        $this->expectException(InvalidDeliveryModeException::class);
        $this->expectExceptionMessage('The delivery mode must be one of [2,1].');

        InvalidDeliveryModeException::assertValidDeliveryMode('is-not-valid');
    }

    public function testShouldDoNothingIfDeliveryModeIsValid()
    {
        InvalidDeliveryModeException::assertValidDeliveryMode(DeliveryMode::PERSISTENT);
        InvalidDeliveryModeException::assertValidDeliveryMode(DeliveryMode::NON_PERSISTENT);
    }
}
