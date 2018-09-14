<?php

namespace Enqueue\Client;

class RouteCollection
{
    /**
     * @var Route[]
     */
    private $routes;

    /**
     * @var Route[]
     */
    private $commandRoutes;

    /**
     * @var Route[]
     */
    private $topicRoutes;

    /**
     * @param Route[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function add(Route $route): void
    {
        $this->routes[] = $route;
        $this->topicRoutes = null;
        $this->commandRoutes = null;
    }

    /**
     * @return Route[]
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * @return Route[]
     */
    public function command(string $command): ?Route
    {
        if (null === $this->commandRoutes) {
            $commandRoutes = [];
            foreach ($this->routes as $route) {
                if ($route->isCommand()) {
                    $commandRoutes[$route->getSource()] = $route;
                }
            }

            $this->commandRoutes = $commandRoutes;
        }

        return array_key_exists($command, $this->commandRoutes) ? $this->commandRoutes[$command] : null;
    }

    /**
     * @return Route[]
     */
    public function topic(string $topic): array
    {
        if (null === $this->topicRoutes) {
            $topicRoutes = [];
            foreach ($this->routes as $route) {
                if ($route->isTopic()) {
                    $topicRoutes[$route->getSource()][$route->getProcessor()] = $route;
                }
            }

            $this->topicRoutes = $topicRoutes;
        }

        return array_key_exists($topic, $this->topicRoutes) ? $this->topicRoutes[$topic] : [];
    }

    public function topicAndProcessor(string $topic, string $processor): ?Route
    {
        $routes = $this->topic($topic);
        foreach ($routes as $route) {
            if ($route->getProcessor() === $processor) {
                return $route;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        $rawRoutes = [];
        foreach ($this->routes as $route) {
            $rawRoutes[] = $route->toArray();
        }

        return $rawRoutes;
    }

    public static function fromArray(array $rawRoutes): self
    {
        $routes = [];
        foreach ($rawRoutes as $rawRoute) {
            $routes[] = Route::fromArray($rawRoute);
        }

        return new self($routes);
    }
}
