<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class ProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        self::assertClassImplements(ProducerInterface::class, Producer::class);
    }

    public function testShouldBeFinal()
    {
        self::assertClassFinal(Producer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new Producer($this->createDriverMock(), $this->createRpcFactoryMock());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        new Producer(
            $this->createDriverMock(),
            $this->createRpcFactoryMock(),
            $this->createMock(ExtensionInterface::class)
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createRpcFactoryMock(): RpcFactory
    {
        return $this->createMock(RpcFactory::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createDriverMock(): DriverInterface
    {
        return $this->createMock(DriverInterface::class);
    }
}
