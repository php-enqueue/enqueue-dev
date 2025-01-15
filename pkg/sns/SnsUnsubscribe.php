<?php

declare(strict_types=1);

namespace Enqueue\Sns;

class SnsUnsubscribe
{
    /**
     * @var SnsDestination
     */
    private $topic;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $protocol;

    public function __construct(
        SnsDestination $topic,
        string $endpoint,
        string $protocol,
    ) {
        $this->topic = $topic;
        $this->endpoint = $endpoint;
        $this->protocol = $protocol;
    }

    public function getTopic(): SnsDestination
    {
        return $this->topic;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }
}
