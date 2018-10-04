<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Tests\Symfony\Consumption\Mock\QueueConsumerOptionsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class QueueConsumerOptionsCommandTraitTest extends TestCase
{
    public function testShouldAddExtensionsOptions()
    {
        $trait = new QueueConsumerOptionsCommand($this->createQueueConsumer());

        $options = $trait->getDefinition()->getOptions();

        $this->assertCount(2, $options);
        $this->assertArrayHasKey('idle-time', $options);
        $this->assertArrayHasKey('receive-timeout', $options);
    }

    public function testShouldSetQueueConsumerOptions()
    {
        $consumer = $this->createQueueConsumer();
        $consumer
            ->expects($this->once())
            ->method('setIdleTimeout')
            ->with($this->identicalTo(123.1))
        ;
        $consumer
            ->expects($this->once())
            ->method('setReceiveTimeout')
            ->with($this->identicalTo(456.1))
        ;

        $trait = new QueueConsumerOptionsCommand($consumer);

        $tester = new CommandTester($trait);
        $tester->execute([
            '--idle-time' => '123.1',
            '--receive-timeout' => '456.1',
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumerInterface
     */
    private function createQueueConsumer()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }
}
