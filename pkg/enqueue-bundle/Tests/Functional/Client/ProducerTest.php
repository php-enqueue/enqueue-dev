<?php

namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\RouterProcessor;
use Enqueue\Client\TraceableProducer;
use Enqueue\Rpc\Promise;

/**
 * @group functional
 */
class ProducerTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        static::$container->get(Producer::class)->clearTraces();
    }

    public function tearDown()
    {
        static::$container->get(Producer::class)->clearTraces();

        parent::tearDown();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $messageProducer = static::$container->get(Producer::class);

        $this->assertInstanceOf(ProducerInterface::class, $messageProducer);
    }

    public function testCouldBeGetFromContainerAsShortenAlias()
    {
        $messageProducer = static::$container->get(Producer::class);
        $aliasMessageProducer = static::$container->get('enqueue.producer');

        $this->assertSame($messageProducer, $aliasMessageProducer);
    }

    public function testShouldSendEvent()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get(Producer::class);

        $producer->sendEvent('theTopic', 'theMessage');

        $traces = $this->getTraceableProducer()->getTopicTraces('theTopic');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
    }

    public function testShouldSendCommandWithoutNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get(Producer::class);

        $result = $producer->sendCommand('theCommand', 'theMessage', false);

        $this->assertNull($result);

        $traces = $this->getTraceableProducer()->getTopicTraces(Config::COMMAND_TOPIC);

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
    }

    public function testShouldSendMessageInstanceAsCommandWithoutNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get(Producer::class);

        $message = new Message('theMessage');

        $result = $producer->sendCommand('theCommand', $message, false);

        $this->assertNull($result);

        $traces = $this->getTraceableProducer()->getTopicTraces(Config::COMMAND_TOPIC);

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.topic_name' => Config::COMMAND_TOPIC,
            'enqueue.processor_name' => RouterProcessor::class,
            'enqueue.command_name' => 'theCommand',
            'enqueue.processor_queue_name' => 'default',
            // compatibility with 0.9x
            'enqueue.command' => 'theCommand',
            'enqueue.topic' => '__command__',
        ], $traces[0]['properties']);
    }

    public function testShouldSendExclusiveCommandWithNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get(Producer::class);

        $message = new Message('theMessage');

        $result = $producer->sendCommand('theExclusiveCommandName', $message, false);

        $this->assertNull($result);

        $traces = $this->getTraceableProducer()->getCommandTraces('theExclusiveCommandName');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.topic_name' => Config::COMMAND_TOPIC,
            'enqueue.processor_name' => 'theExclusiveCommandName',
            'enqueue.command_name' => 'theExclusiveCommandName',
            'enqueue.processor_queue_name' => 'the_exclusive_command_queue',
            // compatibility with 0.9x
            'enqueue.command' => 'theExclusiveCommandName',
            'enqueue.topic' => '__command__',
        ], $traces[0]['properties']);
    }

    public function testShouldSendMessageInstanceCommandWithNeedForReply()
    {
        /** @var ProducerInterface $producer */
        $producer = static::$container->get(Producer::class);

        $message = new Message('theMessage');

        $result = $producer->sendCommand('theCommand', $message, true);

        $this->assertInstanceOf(Promise::class, $result);

        $traces = $this->getTraceableProducer()->getCommandTraces('theCommand');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.topic_name' => Config::COMMAND_TOPIC,
            'enqueue.processor_name' => RouterProcessor::class,
            'enqueue.command_name' => 'theCommand',
            'enqueue.processor_queue_name' => 'default',
            // compatibility with 0.9x
            'enqueue.command' => 'theCommand',
            'enqueue.topic' => '__command__',
        ], $traces[0]['properties']);
    }

    /**
     * @return TraceableProducer|object
     */
    private function getTraceableProducer()
    {
        return static::$container->get(Producer::class);
    }
}
