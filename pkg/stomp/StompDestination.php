<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class StompDestination implements Topic, Queue
{
    const TYPE_TOPIC = 'topic';
    const TYPE_EXCHANGE = 'exchange';
    const TYPE_QUEUE = 'queue';
    const TYPE_AMQ_QUEUE = 'amq/queue';
    const TYPE_TEMP_QUEUE = 'temp-queue';
    const TYPE_REPLY_QUEUE = 'reply-queue';

    const HEADER_DURABLE = 'durable';
    const HEADER_AUTO_DELETE = 'auto-delete';
    const HEADER_EXCLUSIVE = 'exclusive';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var array
     */
    private $headers;

    public function __construct()
    {
        $this->headers = [
            self::HEADER_DURABLE => false,
            self::HEADER_AUTO_DELETE => true,
            self::HEADER_EXCLUSIVE => false,
        ];
    }

    public function getStompName(): string
    {
        return $this->name;
    }

    public function setStompName(string $name): void
    {
        $this->name = $name;
    }

    public function getQueueName(): string
    {
        if (empty($this->getStompName())) {
            throw new \LogicException('Destination name is not set');
        }

        $name = '/'.$this->getType().'/'.$this->getStompName();

        if ($this->getRoutingKey()) {
            $name .= '/'.$this->getRoutingKey();
        }

        return $name;
    }

    public function getTopicName(): string
    {
        return $this->getQueueName();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $types = [
            self::TYPE_TOPIC,
            self::TYPE_EXCHANGE,
            self::TYPE_QUEUE,
            self::TYPE_AMQ_QUEUE,
            self::TYPE_TEMP_QUEUE,
            self::TYPE_REPLY_QUEUE,
        ];

        if (false == in_array($type, $types, true)) {
            throw new \LogicException(sprintf('Invalid destination type: "%s"', $type));
        }

        $this->type = $type;
    }

    public function getRoutingKey(): ?string
    {
        return $this->routingKey;
    }

    public function setRoutingKey(string $routingKey = null): void
    {
        $this->routingKey = $routingKey;
    }

    public function isDurable(): bool
    {
        return $this->getHeader(self::HEADER_DURABLE, false);
    }

    public function setDurable(bool $durable): void
    {
        $this->setHeader(self::HEADER_DURABLE, $durable);
    }

    public function isAutoDelete(): bool
    {
        return $this->getHeader(self::HEADER_AUTO_DELETE, false);
    }

    public function setAutoDelete(bool $autoDelete): void
    {
        $this->setHeader(self::HEADER_AUTO_DELETE, $autoDelete);
    }

    public function isExclusive(): bool
    {
        return $this->getHeader(self::HEADER_EXCLUSIVE, false);
    }

    public function setExclusive(bool $exclusive): void
    {
        $this->setHeader(self::HEADER_EXCLUSIVE, $exclusive);
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }
}
