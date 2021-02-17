<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\ProcessorRegistryInterface;
use Enqueue\Symfony\ContainerProcessorRegistry;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerProcessorRegistryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorRegistryInterface()
    {
        $this->assertClassImplements(ProcessorRegistryInterface::class, ContainerProcessorRegistry::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(ContainerProcessorRegistry::class);
    }

    public function testCouldBeConstructedWithContainerAsFirstArgument()
    {
        new ContainerProcessorRegistry($this->createContainerMock());
    }

    public function testShouldAllowGetProcessor()
    {
        $processorMock = $this->createProcessorMock();

        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('processor-name')
            ->willReturn(true)
        ;
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('processor-name')
            ->willReturn($processorMock)
        ;

        $registry = new ContainerProcessorRegistry($containerMock);
        $this->assertSame($processorMock, $registry->get('processor-name'));
    }

    public function testThrowErrorIfServiceDoesNotImplementProcessorReturnType()
    {
        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('processor-name')
            ->willReturn(true)
        ;
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('processor-name')
            ->willReturn(new \stdClass())
        ;

        $registry = new ContainerProcessorRegistry($containerMock);

        $this->expectException(\TypeError::class);
        // Exception messages vary slightly between versions
        $this->expectExceptionMessageMatches(
            '/Enqueue\\\\Symfony\\\\ContainerProcessorRegistry::get\(\).+ Interop\\\\Queue\\\\Processor,.*stdClass returned/'
        );

        $registry->get('processor-name');
    }

    public function testShouldThrowExceptionIfProcessorIsNotSet()
    {
        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('processor-name')
            ->willReturn(false)
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service locator does not have a processor with name "processor-name".');

        $registry = new ContainerProcessorRegistry($containerMock);
        $registry->get('processor-name');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createProcessorMock(): Processor
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createContainerMock(): ContainerInterface
    {
        return $this->createMock(ContainerInterface::class);
    }
}
