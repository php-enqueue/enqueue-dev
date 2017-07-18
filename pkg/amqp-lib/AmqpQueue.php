<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\PsrQueue;

class AmqpQueue implements PsrQueue
{
    /**
     * @var string
     */
    private $name;

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
    private $exclusive;

    /**
     * @var bool
     */
    private $autoDelete;

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
    private $consumerTag;

    /**
     * @var bool
     */
    private $noLocal;

    /**
     * @var bool
     */
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

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->name;
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
    public function isExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param bool $exclusive
     */
    public function setExclusive($exclusive)
    {
        $this->exclusive = (bool) $exclusive;
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
     * @return bool
     */
    public function isNoLocal()
    {
        return $this->noLocal;
    }

    /**
     * @param bool $noLocal
     */
    public function setNoLocal($noLocal)
    {
        $this->noLocal = $noLocal;
    }

    /**
     * @return bool
     */
    public function isNoAck()
    {
        return $this->noAck;
    }

    /**
     * @param bool $noAck
     */
    public function setNoAck($noAck)
    {
        $this->noAck = $noAck;
    }
}
