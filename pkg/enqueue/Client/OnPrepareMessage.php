<?php

namespace Enqueue\Client;

class OnPrepareMessage
{
    /**
     * @var string|array|Message|\JsonSerializable
     */
    private $message;

    /**
     * @var string|null
     */
    private $topic;

    /**
     * @var string|null
     */
    private $command;

    public function __construct($message, $topic, $command)
    {
        $this->message = $message;
        $this->topic = $topic;
        $this->command = $command;
    }

    /**
     * @return array|Message|\JsonSerializable|string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param array|Message|\JsonSerializable|string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return null|string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return null|string
     */
    public function getCommand()
    {
        return $this->command;
    }
}
