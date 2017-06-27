<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanContext;
use Enqueue\Null\NullQueue;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class GearmanContextTest extends TestCase
{
    use ClassExtensionTrait;

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
