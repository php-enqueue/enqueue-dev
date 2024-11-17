<?php

namespace Enqueue\AsyncEventDispatcher;

use Psr\Container\ContainerInterface;

class ContainerAwareRegistry implements Registry
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string[]
     */
    private $eventsMap;

    /**
     * @var string[]
     */
    private $transformersMap;

    /**
     * @param string[] $eventsMap       [eventName => transformerName]
     * @param string[] $transformersMap [transformerName => transformerServiceId]
     */
    public function __construct(array $eventsMap, array $transformersMap, ContainerInterface $container)
    {
        $this->eventsMap = $eventsMap;
        $this->transformersMap = $transformersMap;
        $this->container = $container;
    }

    public function getTransformerNameForEvent($eventName)
    {
        $transformerName = null;
        if (array_key_exists($eventName, $this->eventsMap)) {
            $transformerName = $this->eventsMap[$eventName];
        } else {
            foreach ($this->eventsMap as $eventNamePattern => $name) {
                if ('/' !== $eventNamePattern[0]) {
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

    public function getTransformer($name)
    {
        if (false == array_key_exists($name, $this->transformersMap)) {
            throw new \LogicException(sprintf('There is no transformer named %s', $name));
        }

        $transformer = $this->container->get($this->transformersMap[$name]);

        if (false == $transformer instanceof EventTransformer) {
            throw new \LogicException(sprintf('The container must return instance of %s but got %s', EventTransformer::class, is_object($transformer) ? $transformer::class : gettype($transformer)));
        }

        return $transformer;
    }
}
