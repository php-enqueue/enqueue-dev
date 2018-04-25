<?php

namespace Enqueue\Client\Meta;

use Enqueue\Client\Config;

class QueueMetaRegistry
{
    /**
     * @var array
     */
    private $meta;

    /**
     * @var Config
     */
    private $config;

    /**
     * $meta = [
     *   'aQueueName' => [
     *     'transportName' => 'aTransportQueueName',
     *     'processors' => ['aFooProcessorName', 'aBarProcessorName'],
     *   ]
     * ].
     *
     *
     * @param Config $config
     * @param array  $meta
     */
    public function __construct(Config $config, array $meta)
    {
        $this->config = $config;
        $this->meta = $meta;
    }

    /**
     * @param string      $queueName
     * @param string|null $transportName
     */
    public function add($queueName, $transportName = null)
    {
        $this->meta[$queueName] = [
            'transportName' => $transportName,
            'processors' => [],
        ];
    }

    /**
     * @param string $queueName
     * @param string $processorName
     */
    public function addProcessor($queueName, $processorName)
    {
        if (false == array_key_exists($queueName, $this->meta)) {
            $this->add($queueName);
        }

        $this->meta[$queueName]['processors'][] = $processorName;
    }

    /**
     * @param string $queueName
     *
     * @return QueueMeta
     */
    public function getQueueMeta($queueName)
    {
        if (false == array_key_exists($queueName, $this->meta)) {
            throw new \InvalidArgumentException(sprintf(
                'The queue meta not found. Requested name `%s`',
                $queueName
            ));
        }

        $transportName = $this->config->createTransportQueueName($queueName);

        $meta = array_replace([
            'processors' => [],
            'transportName' => $transportName,
        ], array_filter($this->meta[$queueName]));

        return new QueueMeta($queueName, $meta['transportName'], $meta['processors']);
    }

    /**
     * @return \Generator|QueueMeta[]
     */
    public function getQueuesMeta()
    {
        foreach (array_keys($this->meta) as $queueName) {
            yield $this->getQueueMeta($queueName);
        }
    }
}
