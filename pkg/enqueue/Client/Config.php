<?php

namespace Enqueue\Client;

class Config
{
    const PARAMETER_TOPIC_NAME = 'enqueue.topic_name';
    const PARAMETER_COMMAND_NAME = 'enqueue.command_name';
    const PARAMETER_PROCESSOR_NAME = 'enqueue.processor_name';
    const PARAMETER_PROCESSOR_QUEUE_NAME = 'enqueue.processor_queue_name';
    const DEFAULT_PROCESSOR_QUEUE_NAME = 'default';
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

    /**
     * @param string $prefix
     * @param string $appName
     * @param string $routerTopicName
     * @param string $routerQueueName
     * @param string $defaultProcessorQueueName
     * @param string $routerProcessorName
     * @param array  $transportConfig
     */
    public function __construct($prefix, $appName, $routerTopicName, $routerQueueName, $defaultProcessorQueueName, $routerProcessorName, array $transportConfig = [])
    {
        $this->prefix = $prefix;
        $this->appName = $appName;
        $this->routerTopicName = $routerTopicName;
        $this->routerQueueName = $routerQueueName;
        $this->defaultProcessorQueueName = $defaultProcessorQueueName;
        $this->routerProcessorName = $routerProcessorName;
        $this->transportConfig = $transportConfig;
    }

    /**
     * @return string
     */
    public function getRouterTopicName()
    {
        return $this->routerTopicName;
    }

    /**
     * @return string
     */
    public function getRouterQueueName()
    {
        return $this->routerQueueName;
    }

    /**
     * @return string
     */
    public function getDefaultProcessorQueueName()
    {
        return $this->defaultProcessorQueueName;
    }

    /**
     * @return string
     */
    public function getRouterProcessorName()
    {
        return $this->routerProcessorName;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function createTransportRouterTopicName($name)
    {
        return strtolower(implode('.', array_filter([trim($this->prefix), trim($name)])));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function createTransportQueueName($name)
    {
        return strtolower(implode('.', array_filter([trim($this->prefix), trim($this->appName), trim($name)])));
    }

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return array
     */
    public function getTransportOption($name, $default = null)
    {
        return array_key_exists($name, $this->transportConfig) ? $this->transportConfig[$name] : $default;
    }

    /**
     * @param string|null $prefix
     * @param string|null $appName
     * @param string|null $routerTopicName
     * @param string|null $routerQueueName
     * @param string|null $defaultProcessorQueueName
     * @param string|null $routerProcessorName
     * @param array       $transportConfig
     *
     * @return static
     */
    public static function create(
        $prefix = null,
        $appName = null,
        $routerTopicName = null,
        $routerQueueName = null,
        $defaultProcessorQueueName = null,
        $routerProcessorName = null,
        array $transportConfig = []
    ) {
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
