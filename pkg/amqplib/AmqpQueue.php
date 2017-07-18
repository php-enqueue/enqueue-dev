<?php

namespace Enqueue\Amqplib;

use Interop\Queue\PsrQueue;

class AmqpQueue implements PsrQueue
{
    private $name;
    private $passive;
    private $durable;
    private $exclusive;
    private $autoDelete;
    private $noWait;
    private $arguments;
    private $ticket;
    private $consumerTag;
    private $noLocal;
    private $noAck;

    public function __construct($name)
    {
        $this->name = $name;
        $this->passive = false;
        $this->durable = false;
        $this->exclusive = false;
        $this->autoDelete = true;
        $this->noWait = false;
        $this->noLocal = false;
        $this->noAck = false;
    }

    public function getQueueName()
    {
        return $this->name;
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

    public function isExclusive()
    {
        return $this->exclusive;
    }

    public function setExclusive($exclusive)
    {
        $this->exclusive = (bool) $exclusive;
    }

    public function isAutoDelete()
    {
        return $this->autoDelete;
    }

    public function setAutoDelete($autoDelete)
    {
        $this->autoDelete = (bool) $autoDelete;
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

    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    public function isNoLocal()
    {
        return $this->noLocal;
    }

    public function setNoLocal($noLocal)
    {
        $this->noLocal = $noLocal;
    }

    public function isNoAck()
    {
        return $this->noAck;
    }

    public function setNoAck($noAck)
    {
        $this->noAck = $noAck;
    }
}
