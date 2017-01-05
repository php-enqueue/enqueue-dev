<?php

namespace Enqueue\Client\Meta;

class TopicMetaRegistry
{
    /**
     * @var array
     */
    protected $meta;

    /**
     * $meta = [
     *   'aTopicName' => [
     *     'description' => 'A desc',
     *     'processors' => ['aProcessorNameFoo', 'aProcessorNameBar],
     *   ],
     * ].
     *
     * @param array $meta
     */
    public function __construct(array $meta)
    {
        $this->meta = $meta;
    }

    /**
     * @param string $topicName
     * @param string $description
     */
    public function add($topicName, $description = null)
    {
        $this->meta[$topicName] = [
            'description' => $description,
            'processors' => [],
        ];
    }

    /**
     * @param string $topicName
     * @param string $processorName
     */
    public function addProcessor($topicName, $processorName)
    {
        if (false == array_key_exists($topicName, $this->meta)) {
            $this->add($topicName);
        }

        $this->meta[$topicName]['processors'][] = $processorName;
    }

    /**
     * @param string $topicName
     *
     * @return TopicMeta
     */
    public function getTopicMeta($topicName)
    {
        if (false == array_key_exists($topicName, $this->meta)) {
            throw new \InvalidArgumentException(sprintf('The topic meta not found. Requested name `%s`', $topicName));
        }

        $topic = array_replace([
            'description' => '',
            'processors' => [],
        ], $this->meta[$topicName]);

        return new TopicMeta($topicName, $topic['description'], $topic['processors']);
    }

    /**
     * @return \Generator|TopicMeta[]
     */
    public function getTopicsMeta()
    {
        foreach (array_keys($this->meta) as $topicName) {
            yield $this->getTopicMeta($topicName);
        }
    }
}
