<?php

namespace Enqueue\Bundle\Events;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ContainerAwareRegistry implements Registry, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string[]
     */
    private $eventNamesMap;

    /**
     * @var string[]
     */
    private $transformerIdsMap;

    /**
     * @param string[] $eventNamesMap
     * @param string[] $transformerIdsMap
     */
    public function __construct(array $eventNamesMap, array $transformerIdsMap)
    {
        $this->eventNamesMap = $eventNamesMap;
        $this->transformerIdsMap = $transformerIdsMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformerNameForEvent($eventName)
    {
        $transformerName = null;
        if (array_key_exists($eventName, $this->eventNamesMap)) {
            $transformerName = $this->eventNamesMap[$eventName];
        } else {
            foreach ($this->eventNamesMap as $eventNamePattern => $name) {
                if ('/' != $eventNamePattern[0]) {
                    continue;
                }

                if (preg_match($eventNamePattern, $eventName)) {
                    $transformerName = $name;

                    break;
                }
            }
        }

        if (empty($transformerName)) {
            throw new \LogicException(sprintf('There is no transformer registered for the given event %s', $eventName));
        }

        return $transformerName;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformer($name)
    {
        if (false == array_key_exists($name, $this->transformerIdsMap)) {
            throw new \LogicException(sprintf('There is no transformer named %s', $name));
        }

        $transformer = $this->container->get($this->transformerIdsMap[$name]);

        if (false == $transformer instanceof  EventTransformer) {
            throw new \LogicException(sprintf(
                'The container must return instance of %s but got %s',
                EventTransformer::class,
                is_object($transformer) ? get_class($transformer) : gettype($transformer)
            ));
        }

        return $transformer;
    }
}
