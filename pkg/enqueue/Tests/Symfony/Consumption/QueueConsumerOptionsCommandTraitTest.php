<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumer;
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
        $this->assertArrayHasKey('idle-timeout', $options);
        $this->assertArrayHasKey('receive-timeout', $options);
    }

    public function testShouldSetQueueConsumerOptions()
    {
        $consumer = $this->createQueueConsumer();
        $consumer
            ->expects($this->once())
            ->method('setIdleTimeout')
            ->with($this->identicalTo(123))
        ;
        $consumer
            ->expects($this->once())
            ->method('setReceiveTimeout')
            ->with($this->identicalTo(456))
        ;

        $trait = new QueueConsumerOptionsCommand($consumer);

        $tester = new CommandTester($trait);
        $tester->execute([
            '--idle-timeout' => '123',
            '--receive-timeout' => '456',
        ]);
    }

    /**
     * @return QueueConsumer|\PHPUnit_Framework_MockObject_MockObject|QueueConsumer
     */
    private function createQueueConsumer()
    {
        return $this->createMock(QueueConsumer::class);
    }
}
