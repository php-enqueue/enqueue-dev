<?php

namespace Enqueue\Client;

class Config
{
    const PARAMETER_TOPIC_NAME = 'enqueue.topic_name';
    const PARAMETER_COMMAND_NAME = 'enqueue.command_name';
    const PARAMETER_PROCESSOR_NAME = 'enqueue.processor_name';

    /**
     * @deprecated
     */
    const PARAMETER_PROCESSOR_QUEUE_NAME = 'enqueue.processor_queue_name';

    /**
     * @deprecated
     */
    const DEFAULT_PROCESSOR_QUEUE_NAME = 'default';

    /**
     * @deprecated
     */
    const COMMAND_TOPIC = '__command__';

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $appName;

    /**
     * @var string
     */
    private $routerTopicName;

    /**
     * @var string
     */
    private $routerQueueName;

    /**
     * @var string
     */
    private $defaultProcessorQueueName;

    /**
     * @var string
     */
    private $routerProcessorName;

    /**
     * @var array
     */
    private $transportConfig;

    public function __construct(string $prefix, string $appName, string $routerTopicName, string $routerQueueName, string $defaultProcessorQueueName, string $routerProcessorName, array $transportConfig = [])
    {
        $this->prefix = trim($prefix);
        $this->appName = trim($appName);

        $this->routerTopicName = trim($routerTopicName);
        if (empty($this->routerTopicName)) {
            throw new \InvalidArgumentException('Router topic is empty.');
        }

        $this->routerQueueName = trim($routerQueueName);
        if (empty($this->routerQueueName)) {
            throw new \InvalidArgumentException('Router queue is empty.');
        }

        $this->defaultProcessorQueueName = trim($defaultProcessorQueueName);
        if (empty($this->defaultProcessorQueueName)) {
            throw new \InvalidArgumentException('Default processor queue name is empty.');
        }

        $this->routerProcessorName = trim($routerProcessorName);
        if (empty($this->routerProcessorName)) {
            throw new \InvalidArgumentException('Router processor name is empty.');
        }

        $this->transportConfig = $transportConfig;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getSeparator(): string
    {
        return '.';
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function getRouterTopicName(): string
    {
        return $this->routerTopicName;
    }

    public function getRouterQueueName(): string
    {
        return $this->routerQueueName;
    }

    public function getDefaultProcessorQueueName(): string
    {
        return $this->defaultProcessorQueueName;
    }

    public function getRouterProcessorName(): string
    {
        return $this->routerProcessorName;
    }

    /**
     * @deprecated
     */
    public function createTransportRouterTopicName(string $name): string
    {
        return strtolower(implode('.', array_filter([trim($this->prefix), trim($name)])));
    }

    /**
     * @deprecated
     */
    public function createTransportQueueName(string $name): string
    {
        return strtolower(implode('.', array_filter([trim($this->prefix), trim($this->appName), trim($name)])));
    }

    /**
     * @deprecated
     *
     * @param null|mixed $default
     */
    public function getTransportOption(string $name, $default = null)
    {
        return array_key_exists($name, $this->transportConfig) ? $this->transportConfig[$name] : $default;
    }

    public static function create(
        string $prefix = null,
        string $appName = null,
        string $routerTopicName = null,
        string $routerQueueName = null,
        string $defaultProcessorQueueName = null,
        string $routerProcessorName = null,
        array $transportConfig = []
    ): self {
        return new static(
            $prefix ?: '',
            $appName ?: '',
            $routerTopicName ?: 'router',
            $routerQueueName ?: 'default',
            $defaultProcessorQueueName ?: 'default',
            $routerProcessorName ?: 'router',
            $transportConfig
        );
    }
}
