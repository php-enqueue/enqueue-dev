<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\PsrTopic;

class AmqpTopic implements PsrTopic
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
     * @var bool
     */
    private $passive;

    /**
     * @var bool
     */
    private $durable;

    /**
     * @var bool
     */
    private $autoDelete;

    /**
     * @var bool
     */
    private $internal;

    /**
     * @var bool
     */
    private $noWait;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var int
     */
    private $ticket;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->passive = false;
        $this->durable = false;
        $this->autoDelete = true;
        $this->internal = false;
        $this->noWait = false;
    }

    /**
     * @return string
     */
    public function getTopicName()
    {
        return $this->name;
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
     * @return bool
     */
    public function isPassive()
    {
        return $this->passive;
    }

    /**
     * @param bool $passive
     */
    public function setPassive($passive)
    {
        $this->passive = (bool) $passive;
    }

    /**
     * @return bool
     */
    public function isDurable()
    {
        return $this->durable;
    }

    /**
     * @param bool $durable
     */
    public function setDurable($durable)
    {
        $this->durable = (bool) $durable;
    }

    /**
     * @return bool
     */
    public function isAutoDelete()
    {
        return $this->autoDelete;
    }

    /**
     * @param bool $autoDelete
     */
    public function setAutoDelete($autoDelete)
    {
        $this->autoDelete = (bool) $autoDelete;
    }

    /**
     * @return bool
     */
    public function isInternal()
    {
        return $this->internal;
    }

    /**
     * @param bool $internal
     */
    public function setInternal($internal)
    {
        $this->internal = (bool) $internal;
    }

    /**
     * @return bool
     */
    public function isNoWait()
    {
        return $this->noWait;
    }

    /**
     * @param bool $noWait
     */
    public function setNoWait($noWait)
    {
        $this->noWait = (bool) $noWait;
    }

    /**
     * @return array|null
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array|null $arguments
     */
    public function setArguments(array $arguments = null)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return int
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @param int $ticket
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
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
