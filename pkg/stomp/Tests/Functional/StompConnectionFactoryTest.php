<?php

namespace Enqueue\Stomp\Tests\Functional;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Test\RabbitmqStompExtension;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Network\Observer\ServerAliveObserver;

class StompConnectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    use RabbitmqStompExtension;

    public function testShouldNotCreateConnectionWithSendHeartbeat()
    {
        $dsn = $this->getDsn().'?send_heartbeat=2000';
        $factory = new StompConnectionFactory($dsn);
        $this->expectException(HeartbeatException::class);
        $factory->createContext()->getStomp();
    }

    public function testShouldCreateConnectionWithSendHeartbeat()
    {
        $dsn = $this->getDsn().'?send_heartbeat=2000&read_timeout=1';
        $factory = new StompConnectionFactory($dsn);
        $context = $factory->createContext();

        $observers = $context->getStomp()->getConnection()->getObservers()->getObservers();
        $this->assertAttributeEquals([2000, 0], 'heartbeat', $context->getStomp());
        $this->assertCount(1, $observers);
        $this->assertInstanceOf(HeartbeatEmitter::class, $observers[0]);
    }

    public function testShouldCreateConnectionWithReceiveHeartbeat()
    {
        $dsn = $this->getDsn().'?receive_heartbeat=2000';
        $factory = new StompConnectionFactory($dsn);
        $context = $factory->createContext();

        $observers = $context->getStomp()->getConnection()->getObservers()->getObservers();
        $this->assertAttributeEquals([0, 2000], 'heartbeat', $context->getStomp());
        $this->assertCount(1, $observers);
        $this->assertInstanceOf(ServerAliveObserver::class, $observers[0]);
    }
}
