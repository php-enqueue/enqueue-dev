<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanContext;
use Enqueue\NoEffect\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class GearmanContextTest extends TestCase
{
    use ClassExtensionTrait;
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, GearmanContext::class);
    }

    public function testCouldBeConstructedWithConnectionConfigAsFirstArgument()
    {
        new GearmanContext(['host' => 'aHost', 'port' => 'aPort']);
    }

    public function testThrowNotImplementedOnCreateTemporaryQueue()
    {
        $context = new GearmanContext(['host' => 'aHost', 'port' => 'aPort']);

        $this->expectException(TemporaryQueueNotSupportedException::class);

        $context->createTemporaryQueue();
    }

    public function testThrowInvalidDestinationIfInvalidDestinationGivenOnCreateConsumer()
    {
        $context = new GearmanContext(['host' => 'aHost', 'port' => 'aPort']);

        $this->expectException(InvalidDestinationException::class);
        $context->createConsumer(new NullQueue('aQueue'));
    }
}
