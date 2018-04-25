<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Client\Meta\TopicMetaRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddTopicMetaPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $topicsMeta;

    public function __construct()
    {
        $this->topicsMeta = [];
    }

    /**
     * @param string $topicName
     * @param string $topicDescription
     * @param array  $topicSubscribers
     *
     * @return $this
     */
    public function add($topicName, $topicDescription = '', array $topicSubscribers = [])
    {
        $this->topicsMeta[$topicName] = [];

        if ($topicDescription) {
            $this->topicsMeta[$topicName]['description'] = $topicDescription;
        }

        if ($topicSubscribers) {
            $this->topicsMeta[$topicName]['processors'] = $topicSubscribers;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $metaRegistryId = TopicMetaRegistry::class;

        if (false == $container->hasDefinition($metaRegistryId)) {
            return;
        }

        $metaRegistry = $container->getDefinition($metaRegistryId);

        $metaRegistry->replaceArgument(0, array_merge_recursive($metaRegistry->getArgument(0), $this->topicsMeta));
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }
}
