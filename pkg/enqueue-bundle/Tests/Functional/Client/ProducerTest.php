<?php

namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Rpc\Promise;

/**
 * @group functional
 */
class ProducerTest extends WebTestCase
{
    public function testCouldBeGetFromContainerByInterface()
    {
        $producer = static::$container->get('test_'.ProducerInterface::class);

        $this->assertInstanceOf(ProducerInterface::class, $producer);
    }

    public function testCouldBeGetFromContainerByServiceId()
    {
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $this->assertInstanceOf(ProducerInterface::class, $producer);
    }

    public function testShouldSendEvent()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $producer->sendEvent('theTopic', 'theMessage');

        $traces = $this->getTraceableProducer()->getTopicTraces('theTopic');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
    }

    public function testShouldSendCommandWithoutNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $result = $producer->sendCommand('theCommand', 'theMessage', false);

        $this->assertNull($result);

        $traces = $this->getTraceableProducer()->getCommandTraces('theCommand');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
    }

    public function testShouldSendMessageInstanceAsCommandWithoutNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $message = new Message('theMessage');

        $result = $producer->sendCommand('theCommand', $message, false);

        $this->assertNull($result);

        $traces = $this->getTraceableProducer()->getCommandTraces('theCommand');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.processor' => 'test_command_subscriber_processor',
            'enqueue.command' => 'theCommand',
        ], $traces[0]['properties']);
    }

    public function testShouldSendExclusiveCommandWithNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $message = new Message('theMessage');

        $result = $producer->sendCommand('theExclusiveCommandName', $message, false);

        $this->assertNull($result);

        $traces = $this->getTraceableProducer()->getCommandTraces('theExclusiveCommandName');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.processor' => 'theExclusiveCommandName',
            'enqueue.command' => 'theExclusiveCommandName',
        ], $traces[0]['properties']);
    }

    public function testShouldSendMessageInstanceCommandWithNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get('test_enqueue.client.default.producer');

        $message = new Message('theMessage');

        $result = $producer->sendCommand('theCommand', $message, true);

        $this->assertInstanceOf(Promise::class, $result);

        $traces = $this->getTraceableProducer()->getCommandTraces('theCommand');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.processor' => 'test_command_subscriber_processor',
            'enqueue.command' => 'theCommand',
        ], $traces[0]['properties']);
    }

    private function getTraceableProducer(): TraceableProducer
    {
        return static::$container->get('test_enqueue.client.default.traceable_producer');
    }
}
