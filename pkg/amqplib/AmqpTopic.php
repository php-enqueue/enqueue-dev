<?php

namespace Enqueue\Amqplib;

use Interop\Queue\PsrTopic;

class AmqpTopic implements PsrTopic
{
    private $name;
    private $type;
    private $passive;
    private $durable;
    private $autoDelete;
    private $internal;
    private $noWait;
    private $arguments;
    private $ticket;
    private $routingKey;

    public function __construct($name)
    {
        $this->name = $name;
        $this->passive = false;
        $this->durable = false;
        $this->autoDelete = true;
        $this->internal = false;
        $this->noWait = false;
    }

    public function getTopicName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function isPassive()
    {
        return $this->passive;
    }

    public function setPassive($passive)
    {
        $this->passive = (bool) $passive;
    }

    public function isDurable()
    {
        return $this->durable;
    }

    public function setDurable($durable)
    {
        $this->durable = (bool) $durable;
    }

    public function isAutoDelete()
    {
        return $this->autoDelete;
    }

    public function setAutoDelete($autoDelete)
    {
        $this->autoDelete = (bool) $autoDelete;
    }

    public function isInternal()
    {
        return $this->internal;
    }

    public function setInternal($internal)
    {
        $this->internal = (bool) $internal;
    }

    public function isNoWait()
    {
        return $this->noWait;
    }

    public function setNoWait($noWait)
    {
        $this->noWait = (bool) $noWait;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments = null)
    {
        $this->arguments = $arguments;
    }

    public function getTicket()
    {
        return $this->ticket;
    }

    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
    }

    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }
}
