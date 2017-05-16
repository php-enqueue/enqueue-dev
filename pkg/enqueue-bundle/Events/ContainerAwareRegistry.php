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
    private $transformersMap;

    /**
     * @param string[] $transformersMap
     */
    public function __construct(array $transformersMap)
    {
        $this->transformersMap = $transformersMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformer($eventName)
    {
        if (false == array_key_exists($eventName, $this->transformersMap)) {
            throw new \LogicException(sprintf('There is no transformer registered for the given event %s', $eventName));
        }

        // TODO add check container returns instance of EventTransformer interface.

        return $this->container->get($this->transformersMap[$eventName]);
    }
}
