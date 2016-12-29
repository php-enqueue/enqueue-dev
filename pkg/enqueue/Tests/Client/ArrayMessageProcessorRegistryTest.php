<?php
namespace Enqueue\Tests\Client;

use Enqueue\Client\ArrayMessageProcessorRegistry;
use Enqueue\Client\MessageProcessorRegistryInterface;
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Test\ClassExtensionTrait;

class ArrayMessageProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorRegistryInterface()
    {
        $this->assertClassImplements(MessageProcessorRegistryInterface::class, ArrayMessageProcessorRegistry::class);
    }

    public function testCouldBeConstructedWithoutAnyArgument()
    {
        new ArrayMessageProcessorRegistry();
    }

    public function testShouldThrowExceptionIfProcessorIsNotSet()
    {
        $registry = new ArrayMessageProcessorRegistry();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('MessageProcessor was not found. processorName: "processor-name"');
        $registry->get('processor-name');
    }

    public function testShouldAllowGetProcessorAddedViaConstructor()
    {
        $processor = $this->createMessageProcessorMock();

        $registry = new ArrayMessageProcessorRegistry(['aFooName' => $processor]);

        $this->assertSame($processor, $registry->get('aFooName'));
    }

    public function testShouldAllowGetProcessorAddedViaAddMethod()
    {
        $processor = $this->createMessageProcessorMock();

        $registry = new ArrayMessageProcessorRegistry();
        $registry->add('aFooName', $processor);

        $this->assertSame($processor, $registry->get('aFooName'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessorMock()
    {
        return $this->createMock(MessageProcessorInterface::class);
    }
}
