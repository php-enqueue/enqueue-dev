<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\ProcessorRegistryInterface;
use Enqueue\Symfony\Client\ContainerAwareProcessorRegistry;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class ContainerAwareProcessorRegistryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorRegistryInterface()
    {
        $this->assertClassImplements(ProcessorRegistryInterface::class, ContainerAwareProcessorRegistry::class);
    }

    public function testCouldBeConstructedWithoutAnyArgument()
    {
        new ContainerAwareProcessorRegistry();
    }

    public function testShouldThrowExceptionIfProcessorIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Processor was not found. processorName: "processor-name"'
        );

        $registry = new ContainerAwareProcessorRegistry();
        $registry->get('processor-name');
    }

    public function testShouldThrowExceptionIfContainerIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Container was not set');

        $registry = new ContainerAwareProcessorRegistry();
        $registry->set('processor-name', 'processor-id');

        $registry->get('processor-name');
    }

    public function testShouldThrowExceptionIfInstanceOfProcessorIsInvalid()
    {
        $this->setExpectedException(\LogicException::class, 'Container was not set');

        $processor = new \stdClass();

        $container = new Container();
        $container->set('processor-id', $processor);

        $registry = new ContainerAwareProcessorRegistry();
        $registry->set('processor-name', 'processor-id');

        $registry->get('processor-name');
    }

    public function testShouldReturnInstanceOfProcessor()
    {
        $this->setExpectedException(\LogicException::class, 'Container was not set');

        $processor = $this->createProcessorMock();

        $container = new Container();
        $container->set('processor-id', $processor);

        $registry = new ContainerAwareProcessorRegistry();
        $registry->set('processor-name', 'processor-id');

        $this->assertSame($processor, $registry->get('processor-name'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProcessor
     */
    protected function createProcessorMock()
    {
        return $this->createMock(PsrProcessor::class);
    }
}
