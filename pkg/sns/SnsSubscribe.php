<?php

declare(strict_types=1);

namespace Enqueue\Sns;

class SnsSubscribe
{
    public const PROTOCOL_SQS = 'sqs';

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

    private $returnSubscriptionArn;

    private $attributes;

    public function __construct(
        SnsDestination $topic,
        string $endpoint,
        string $protocol,
        bool $returnSubscriptionArn = false,
        array $attributes = [],
    ) {
        $this->topic = $topic;
        $this->endpoint = $endpoint;
        $this->protocol = $protocol;
        $this->returnSubscriptionArn = $returnSubscriptionArn;
        $this->attributes = $attributes;
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

    public function isReturnSubscriptionArn(): bool
    {
        return $this->returnSubscriptionArn;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
