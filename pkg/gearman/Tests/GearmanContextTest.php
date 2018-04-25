<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanContext;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class GearmanContextTest extends TestCase
{
    use ClassExtensionTrait;
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    public function testShouldImplementPsrContextInterface()
    {
        $this->assertClassImplements(PsrContext::class, GearmanContext::class);
    }

    public function testCouldBeConstructedWithConnectionConfigAsFirstArgument()
    {
        new GearmanContext(['host' => 'aHost', 'port' => 'aPort']);
    }

    public function testThrowNotImplementedOnCreateTemporaryQueue()
    {
        $context = new GearmanContext(['host' => 'aHost', 'port' => 'aPort']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not implemented');
        $context->createTemporaryQueue();
    }

    public function testThrowInvalidDestinationIfInvalidDestinationGivenOnCreateConsumer()
    {
        $context = new GearmanContext(['host' => 'aHost', 'port' => 'aPort']);

        $this->expectException(InvalidDestinationException::class);
        $context->createConsumer(new NullQueue('aQueue'));
    }
}
