<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use PHPUnit\Framework\TestCase;

class EmptyExtensionTraitTest extends TestCase
{
    public function testTraitMustImplementOrExtensionMethods()
    {
        new EmptyExtension();
    }
}

class EmptyExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;
}
