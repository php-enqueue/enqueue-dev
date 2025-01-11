<?php

namespace Enqueue\Bundle\Tests\Unit;

use Enqueue\Bundle\EnqueueBundle;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EnqueueBundleTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldExtendBundleClass()
    {
        $this->assertClassExtends(Bundle::class, EnqueueBundle::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new EnqueueBundle();
    }
}
