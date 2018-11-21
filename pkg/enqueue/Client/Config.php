<?php

namespace Enqueue\Client;

class Config
{
    const TOPIC = 'enqueue.topic';
    const COMMAND = 'enqueue.command';
    const PROCESSOR = 'enqueue.processor';
    const EXPIRE = 'enqueue.expire';
    const PRIORITY = 'enqueue.priority';
    const DELAY = 'enqueue.delay';
    const CONTENT_TYPE = 'enqueue.content_type';

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $separator;

    /**
     * @var string
     */
    private $app;

    /**
     * @var string
     */
    private $routerTopic;

    /**
     * @var string
     */
    private $routerQueue;

    /**
     * @var string
     */
    private $defaultQueue;

    /**
     * @var string
     */
    private $routerProcessor;

    /**
     * @var array
     */
    private $transportConfig;

    /**
     * @var array
     */
    private $driverConfig;

    public function __construct(
        string $prefix,
        string $separator,
        string $app,
        string $routerTopic,
        string $routerQueue,
        string $defaultQueue,
        string $routerProcessor,
        array $transportConfig,
        array $driverConfig
    ) {
        $this->prefix = trim($prefix);
        $this->app = trim($app);

        $this->routerTopic = trim($routerTopic);
        if (empty($this->routerTopic)) {
            throw new \InvalidArgumentException('Router topic is empty.');
        }

        $this->routerQueue = trim($routerQueue);
        if (empty($this->routerQueue)) {
            throw new \InvalidArgumentException('Router queue is empty.');
        }

        $this->defaultQueue = trim($defaultQueue);
        if (empty($this->defaultQueue)) {
            throw new \InvalidArgumentException('Default processor queue name is empty.');
        }

        $this->routerProcessor = trim($routerProcessor);
        if (empty($this->routerProcessor)) {
            throw new \InvalidArgumentException('Router processor name is empty.');
        }

        $this->transportConfig = $transportConfig;
        $this->driverConfig = $driverConfig;

        $this->separator = $separator;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function getRouterTopic(): string
    {
        return $this->routerTopic;
    }

    public function getRouterQueue(): string
    {
        return $this->routerQueue;
    }

    public function getDefaultQueue(): string
    {
        return $this->defaultQueue;
    }

    public function getRouterProcessor(): string
    {
        return $this->routerProcessor;
    }

    public function getTransportOption(string $name, $default = null)
    {
        return array_key_exists($name, $this->transportConfig) ? $this->transportConfig[$name] : $default;
    }

    public function getTransportOptions(): array
    {
        return $this->transportConfig;
    }

    public function getDriverOption(string $name, $default = null)
    {
        return array_key_exists($name, $this->driverConfig) ? $this->driverConfig[$name] : $default;
    }

    public function getDriverOptions(): array
    {
        return $this->driverConfig;
    }

    public static function create(
        string $prefix = null,
        string $separator = null,
        string $app = null,
        string $routerTopic = null,
        string $routerQueue = null,
        string $defaultQueue = null,
        string $routerProcessor = null,
        array $transportConfig = [],
        array $driverConfig = []
    ): self {
        return new static(
            $prefix ?: '',
            $separator ?: '.',
            $app ?: '',
            $routerTopic ?: 'router',
            $routerQueue ?: 'default',
            $defaultQueue ?: 'default',
            $routerProcessor ?: 'router',
            $transportConfig,
            $driverConfig
        );
    }
}
