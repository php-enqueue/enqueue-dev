<?php
namespace Enqueue\AmqpExt;

use Enqueue\Psr\Topic;

class AmqpTopic implements Topic
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;

        $this->type = AMQP_EX_TYPE_DIRECT;
        $this->flags = AMQP_NOPARAM;
        $this->arguments = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setTopicName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param int $flag
     */
    public function addFlag($flag)
    {
        $this->flags |= $flag;
    }

    public function clearFlags()
    {
        $this->flags = AMQP_NOPARAM;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments = null)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @param string $routingKey
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }
}
