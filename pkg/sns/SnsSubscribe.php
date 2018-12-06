<?php

declare(strict_types=1);

namespace Enqueue\Sns;

class SnsSubscribe
{
    private $destination;

    private $endpoint;

    private $protocol;

    private $attributes;

    public function __construct(SnsDestination $destination, string $endpoint, string $protocol, array $arguments)
    {
        $this->destination = $destination;
        $this->endpoint = $endpoint;
        $this->protocol = $protocol;
        $this->attributes = $arguments;
    }

    public function getDestination(): SnsDestination
    {
        return $this->destination;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
