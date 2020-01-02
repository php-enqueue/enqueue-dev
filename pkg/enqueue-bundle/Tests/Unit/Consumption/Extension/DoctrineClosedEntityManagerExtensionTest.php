<?php

namespace Enqueue\Bundle\Tests\Unit\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Bundle\Consumption\Extension\DoctrineClosedEntityManagerExtension;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Message;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DoctrineClosedEntityManagerExtensionTest extends TestCase
{
    public function testOnPreConsumeShouldNotInterruptExecution()
    {
        $manager = $this->createManagerMock(true);

        $registry = $this->createRegistryMock([
            'manager' => $manager,
        ]);

        $message = new PreConsume(
            $this->createMock(InteropContext::class),
            $this->createMock(SubscriptionConsumer::class),
            $this->createMock(LoggerInterface::class),
            1,
            2,
            3
        );

        self::assertFalse($message->isExecutionInterrupted());

        $extension = new DoctrineClosedEntityManagerExtension($registry);
        $extension->onPreConsume($message);

        self::assertFalse($message->isExecutionInterrupted());
    }

    public function testOnPreConsumeShouldInterruptExecutionIfAManagerIsClosed()
    {
        $manager1 = $this->createManagerMock(true);
        $manager2 = $this->createManagerMock(false);

        $registry = $this->createRegistryMock([
            'manager1' => $manager1,
            'manager2' => $manager2,
        ]);

        $message = new PreConsume(
            $this->createMock(InteropContext::class),
            $this->createMock(SubscriptionConsumer::class),
            $this->createMock(LoggerInterface::class),
            1,
            2,
            3
        );
        $message->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[DoctrineClosedEntityManagerExtension] Interrupt execution as entity manager "manager2" has been closed')
        ;

        self::assertFalse($message->isExecutionInterrupted());

        $extension = new DoctrineClosedEntityManagerExtension($registry);
        $extension->onPreConsume($message);

        self::assertTrue($message->isExecutionInterrupted());
    }

    public function testOnPostConsumeShouldNotInterruptExecution()
    {
        $manager = $this->createManagerMock(true);

        $registry = $this->createRegistryMock([
            'manager' => $manager,
        ]);

        $message = new PostConsume(
            $this->createMock(InteropContext::class),
            $this->createMock(SubscriptionConsumer::class),
            1,
            1,
            1,
            $this->createMock(LoggerInterface::class)
        );

        self::assertFalse($message->isExecutionInterrupted());

        $extension = new DoctrineClosedEntityManagerExtension($registry);
        $extension->onPostConsume($message);

        self::assertFalse($message->isExecutionInterrupted());
    }

    public function testOnPostConsumeShouldInterruptExecutionIfAManagerIsClosed()
    {
        $manager1 = $this->createManagerMock(true);
        $manager2 = $this->createManagerMock(false);

        $registry = $this->createRegistryMock([
            'manager1' => $manager1,
            'manager2' => $manager2,
        ]);

        $message = new PostConsume(
            $this->createMock(InteropContext::class),
            $this->createMock(SubscriptionConsumer::class),
            1,
            1,
            1,
            $this->createMock(LoggerInterface::class)
        );
        $message->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[DoctrineClosedEntityManagerExtension] Interrupt execution as entity manager "manager2" has been closed')
        ;

        self::assertFalse($message->isExecutionInterrupted());

        $extension = new DoctrineClosedEntityManagerExtension($registry);
        $extension->onPostConsume($message);

        self::assertTrue($message->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldNotInterruptExecution()
    {
        $manager = $this->createManagerMock(true);

        $registry = $this->createRegistryMock([
            'manager' => $manager,
        ]);

        $message = new PostMessageReceived(
            $this->createMock(InteropContext::class),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            'aResult',
            1,
            $this->createMock(LoggerInterface::class)
        );

        self::assertFalse($message->isExecutionInterrupted());

        $extension = new DoctrineClosedEntityManagerExtension($registry);
        $extension->onPostMessageReceived($message);

        self::assertFalse($message->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfAManagerIsClosed()
    {
        $manager1 = $this->createManagerMock(true);
        $manager2 = $this->createManagerMock(false);

        $registry = $this->createRegistryMock([
            'manager1' => $manager1,
            'manager2' => $manager2,
        ]);

        $message = new PostMessageReceived(
            $this->createMock(InteropContext::class),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            'aResult',
            1,
            $this->createMock(LoggerInterface::class)
        );
        $message->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[DoctrineClosedEntityManagerExtension] Interrupt execution as entity manager "manager2" has been closed')
        ;

        self::assertFalse($message->isExecutionInterrupted());

        $extension = new DoctrineClosedEntityManagerExtension($registry);
        $extension->onPostMessageReceived($message);

        self::assertTrue($message->isExecutionInterrupted());
    }

    /**
     * @return MockObject|ManagerRegistry
     */
    protected function createRegistryMock(array $managers): ManagerRegistry
    {
        $mock = $this->createMock(ManagerRegistry::class);

        $mock
            ->expects($this->once())
            ->method('getManagers')
            ->willReturn($managers)
        ;

        return $mock;
    }

    /**
     * @return MockObject|EntityManagerInterface
     */
    protected function createManagerMock(bool $open): EntityManagerInterface
    {
        $mock = $this->createMock(EntityManagerInterface::class);

        $mock
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn($open)
        ;

        return $mock;
    }
}
