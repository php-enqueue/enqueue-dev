<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class SnsDestination implements Topic, Queue
{
    private $name;

    private $topicArn;

    private $attributes;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->attributes = [];
    }

    public function getQueueName(): string
    {
        return $this->name;
    }

    public function getTopicName(): string
    {
        return $this->name;
    }

    /**
     * The policy that defines who can access your topic. By default, only the topic owner can publish or subscribe to the topic.
     */
    public function setPolicy(?string $policy = null): void
    {
        $this->setAttribute('Policy', $policy);
    }

    public function getPolicy(): ?string
    {
        return $this->getAttribute('Policy');
    }

    /**
     * The display name to use for a topic with SMS subscriptions.
     */
    public function setDisplayName(?string $displayName = null): void
    {
        $this->setAttribute('DisplayName', $displayName);
    }

    public function getDisplayName(): ?string
    {
        return $this->getAttribute('DisplayName');
    }

    /**
     * The display name to use for a topic with SMS subscriptions.
     */
    public function setDeliveryPolicy(?int $deliveryPolicy = null): void
    {
        $this->setAttribute('DeliveryPolicy', $deliveryPolicy);
    }

    public function getDeliveryPolicy(): ?int
    {
        return $this->getAttribute('DeliveryPolicy');
    }

    /**
     * Only FIFO.
     *
     * Designates a topic as FIFO. You can provide this attribute only during queue creation.
     * You can't change it for an existing topic. When you set this attribute, you must provide aMessageGroupId
     * explicitly.
     * For more information, see https://docs.aws.amazon.com/sns/latest/dg/sns-fifo-topics.html
     */
    public function setFifoTopic(bool $enable): void
    {
        $value = $enable ? 'true' : null;

        $this->setAttribute('FifoTopic', $value);
    }

    /**
     * Only FIFO.
     *
     *  Enables content-based deduplication.
     *  For more information, see: https://docs.aws.amazon.com/sns/latest/dg/fifo-message-dedup.html
     */
    public function setContentBasedDeduplication(bool $enable): void
    {
        $value = $enable ? 'true' : null;

        $this->setAttribute('ContentBasedDeduplication', $value);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    private function getAttribute(string $name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    private function setAttribute(string $name, $value): void
    {
        if (null == $value) {
            unset($this->attributes[$name]);
        } else {
            $this->attributes[$name] = $value;
        }
    }
}
