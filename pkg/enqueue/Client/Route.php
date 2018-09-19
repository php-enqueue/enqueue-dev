<?php

namespace Enqueue\Client;

final class Route
{
    const TOPIC = 'enqueue.client.topic_route';

    const COMMAND = 'enqueue.client.command_route';

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $sourceType;

    /**
     * @var string
     */
    private $processor;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        string $source,
        string $sourceType,
        string $processor,
        array $options = []
    ) {
        $this->source = $source;
        $this->sourceType = $sourceType;
        $this->processor = $processor;
        $this->options = $options;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function isCommand(): bool
    {
        return self::COMMAND === $this->sourceType;
    }

    public function isTopic(): bool
    {
        return self::TOPIC === $this->sourceType;
    }

    public function getProcessor(): string
    {
        return $this->processor;
    }

    public function isProcessorExclusive(): bool
    {
        return (bool) $this->getOption('exclusive', false);
    }

    public function isProcessorExternal(): bool
    {
        return (bool) $this->getOption('external', false);
    }

    public function getQueue(): ?string
    {
        return $this->getOption('queue');
    }

    public function isPrefixQueue(): bool
    {
        return (bool) $this->getOption('prefix_queue', true);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, $default = null)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    public function toArray(): array
    {
        return array_replace($this->options, [
            'source' => $this->source,
            'source_type' => $this->sourceType,
            'processor' => $this->processor,
        ]);
    }

    public static function fromArray(array $route): self
    {
        list(
            'source' => $source,
            'source_type' => $sourceType,
            'processor' => $processor) = $route;

        unset($route['source'], $route['source_type'], $route['processor']);
        $options = $route;

        return new self($source, $sourceType, $processor, $options);
    }
}
