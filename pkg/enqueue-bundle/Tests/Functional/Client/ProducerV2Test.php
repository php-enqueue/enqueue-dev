<?php

namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\Config;
use Enqueue\Client\ProducerV2Interface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Rpc\Promise;

/**
 * @group functional
 */
class ProducerV2Test extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->container->get('enqueue.client.producer')->clearTraces();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->container->get('enqueue.client.producer')->clearTraces();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $producer = $this->container->get('enqueue.client.producer_v2');

        $this->assertInstanceOf(ProducerV2Interface::class, $producer);
    }

    public function testShouldSendEvent()
    {
        /** @var ProducerV2Interface $producer */
        $producer = $this->container->get('enqueue.client.producer_v2');

        $producer->sendEvent('theTopic', 'theMessage');

        $traces = $this->getTraceableProducer()->getTopicTraces('theTopic');

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
    }

    public function testShouldSendCommandWithoutNeedForReply()
    {
        /** @var ProducerV2Interface $producer */
        $producer = $this->container->get('enqueue.client.producer_v2');

        $result = $producer->sendCommand('theCommand', 'theMessage', false);

        $this->assertNull($result);

        $traces = $this->getTraceableProducer()->getTopicTraces(Config::COMMAND_TOPIC);

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.topic_name' => Config::COMMAND_TOPIC,
            'enqueue.processor_name' => 'theCommand',
            'enqueue.processor_queue_name' => 'default',
        ], $traces[0]['properties']);
    }

    public function testShouldSendCommandWithNeedForReply()
    {
        /** @var ProducerV2Interface $producer */
        $producer = $this->container->get('enqueue.client.producer_v2');

        $result = $producer->sendCommand('theCommand', 'theMessage', true);

        $this->assertInstanceOf(Promise::class, $result);

        $traces = $this->getTraceableProducer()->getTopicTraces(Config::COMMAND_TOPIC);

        $this->assertCount(1, $traces);
        $this->assertEquals('theMessage', $traces[0]['body']);
        $this->assertEquals([
            'enqueue.topic_name' => Config::COMMAND_TOPIC,
            'enqueue.processor_name' => 'theCommand',
            'enqueue.processor_queue_name' => 'default',
        ], $traces[0]['properties']);
    }

    /**
     * @return TraceableProducer|object
     */
    private function getTraceableProducer()
    {
        return $this->container->get('enqueue.client.producer');
    }
}
