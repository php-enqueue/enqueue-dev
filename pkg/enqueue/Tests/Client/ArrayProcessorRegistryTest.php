<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\ArrayProcessorRegistry;
use Enqueue\Client\ProcessorRegistryInterface;
use Enqueue\Psr\Processor;
use Enqueue\Test\ClassExtensionTrait;

class ArrayProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorRegistryInterface()
    {
        $this->assertClassImplements(ProcessorRegistryInterface::class, ArrayProcessorRegistry::class);
    }

    public function testCouldBeConstructedWithoutAnyArgument()
    {
        new ArrayProcessorRegistry();
    }

    public function testShouldThrowExceptionIfProcessorIsNotSet()
    {
        $registry = new ArrayProcessorRegistry();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor was not found. processorName: "processor-name"');
        $registry->get('processor-name');
    }

    public function testShouldAllowGetProcessorAddedViaConstructor()
    {
        $processor = $this->createProcessorMock();

        $registry = new ArrayProcessorRegistry(['aFooName' => $processor]);

        $this->assertSame($processor, $registry->get('aFooName'));
    }

    public function testShouldAllowGetProcessorAddedViaAddMethod()
    {
        $processor = $this->createProcessorMock();

        $registry = new ArrayProcessorRegistry();
        $registry->add('aFooName', $processor);

        $this->assertSame($processor, $registry->get('aFooName'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Processor
     */
    protected function createProcessorMock()
    {
        return $this->createMock(Processor::class);
    }
}
