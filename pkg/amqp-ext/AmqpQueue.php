<?php
namespace Enqueue\AmqpExt;

use Enqueue\Psr\Queue;

class AmqpQueue implements Queue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var array
     */
    private $bindArguments;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;

        $this->arguments = [];
        $this->bindArguments = [];
        $this->flags = AMQP_NOPARAM;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setQueueName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * @param string $consumerTag
     */
    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
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
     * @return array
     */
    public function getBindArguments()
    {
        return $this->bindArguments;
    }

    /**
     * @param array $arguments
     */
    public function setBindArguments(array $arguments = null)
    {
        $this->bindArguments = $arguments;
    }
}
