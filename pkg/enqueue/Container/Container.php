<?php

namespace Enqueue\Container;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    private $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    public function get($id)
    {
        if (false == $this->has($id)) {
            throw new NotFoundException(sprintf('The service "%s" not found.', $id));
        }

        return $this->services[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->services);
    }
}
