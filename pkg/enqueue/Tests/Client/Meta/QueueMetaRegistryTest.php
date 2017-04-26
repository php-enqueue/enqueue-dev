<?php

namespace Enqueue\Tests\Client\Meta;

use Enqueue\Client\Config;
use Enqueue\Client\Meta\QueueMeta;
use Enqueue\Client\Meta\QueueMetaRegistry;
use PHPUnit\Framework\TestCase;

class QueueMetaRegistryTest extends TestCase
{
    public function testCouldBeConstructedWithQueues()
    {
        $meta = [
            'aQueueName' => [],
            'anotherQueueName' => [],
        ];

        $registry = new QueueMetaRegistry($this->createConfig(), $meta);

        $this->assertAttributeEquals($meta, 'meta', $registry);
    }

    public function testShouldAllowAddQueueMetaUsingAddMethod()
    {
        $registry = new QueueMetaRegistry($this->createConfig(), []);

        $registry->add('theFooQueueName', 'theTransportQueueName');
        $registry->add('theBarQueueName');

        $this->assertAttributeSame([
            'theFooQueueName' => [
                'transportName' => 'theTransportQueueName',
                'processors' => [],
            ],
            'theBarQueueName' => [
                'transportName' => null,
                'processors' => [],
            ],
        ], 'meta', $registry);
    }

    public function testShouldAllowAddSubscriber()
    {
        $registry = new QueueMetaRegistry($this->createConfig(), []);

        $registry->addProcessor('theFooQueueName', 'theFooProcessorName');
        $registry->addProcessor('theFooQueueName', 'theBarProcessorName');
        $registry->addProcessor('theBarQueueName', 'theBazProcessorName');

        $this->assertAttributeSame([
            'theFooQueueName' => [
                'transportName' => null,
                'processors' => ['theFooProcessorName', 'theBarProcessorName'],
            ],
            'theBarQueueName' => [
                'transportName' => null,
                'processors' => ['theBazProcessorName'],
            ],
        ], 'meta', $registry);
    }

    public function testThrowIfThereIsNotMetaForRequestedClientQueueName()
    {
        $registry = new QueueMetaRegistry($this->createConfig(), []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The queue meta not found. Requested name `aName`');
        $registry->getQueueMeta('aName');
    }

    public function testShouldAllowGetQueueByNameWithDefaultInfo()
    {
        $queues = [
            'theQueueName' => [],
        ];

        $registry = new QueueMetaRegistry($this->createConfig(), $queues);

        $queue = $registry->getQueueMeta('theQueueName');

        $this->assertInstanceOf(QueueMeta::class, $queue);
        $this->assertSame('theQueueName', $queue->getClientName());
        $this->assertSame('aprefix.anappname.thequeuename', $queue->getTransportName());
        $this->assertSame([], $queue->getProcessors());
    }

    public function testShouldAllowGetQueueByNameWithCustomInfo()
    {
        $queues = [
            'theClientQueueName' => ['transportName' => 'theTransportName', 'processors' => ['theSubscriber']],
        ];

        $registry = new QueueMetaRegistry($this->createConfig(), $queues);

        $queue = $registry->getQueueMeta('theClientQueueName');
        $this->assertInstanceOf(QueueMeta::class, $queue);
        $this->assertSame('theClientQueueName', $queue->getClientName());
        $this->assertSame('theTransportName', $queue->getTransportName());
        $this->assertSame(['theSubscriber'], $queue->getProcessors());
    }

    public function testShouldNotAllowToOverwriteDefaultTransportNameByEmptyValue()
    {
        $registry = new QueueMetaRegistry($this->createConfig(), [
            'theClientQueueName' => ['transportName' => null, 'processors' => []],
        ]);

        $queue = $registry->getQueueMeta('theClientQueueName');
        $this->assertInstanceOf(QueueMeta::class, $queue);
        $this->assertSame('aprefix.anappname.theclientqueuename', $queue->getTransportName());
    }

    public function testShouldAllowGetAllQueues()
    {
        $queues = [
            'fooQueueName' => [],
            'barQueueName' => [],
        ];

        $registry = new QueueMetaRegistry($this->createConfig(), $queues);

        $queues = $registry->getQueuesMeta();
        $this->assertInstanceOf(\Generator::class, $queues);

        $queues = iterator_to_array($queues);
        /* @var QueueMeta[] $queues */

        $this->assertContainsOnly(QueueMeta::class, $queues);
        $this->assertCount(2, $queues);

        $this->assertSame('fooQueueName', $queues[0]->getClientName());
        $this->assertSame('aprefix.anappname.fooqueuename', $queues[0]->getTransportName());

        $this->assertSame('barQueueName', $queues[1]->getClientName());
        $this->assertSame('aprefix.anappname.barqueuename', $queues[1]->getTransportName());
    }

    /**
     * @return Config
     */
    private function createConfig()
    {
        return new Config('aPrefix', 'anAppName', 'aRouterTopic', 'aRouterQueueName', 'aDefaultQueueName', 'aRouterProcessorName');
    }
}
