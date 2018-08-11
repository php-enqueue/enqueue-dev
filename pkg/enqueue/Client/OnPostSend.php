<?php

namespace Enqueue\Client;

class OnPostSend
{
    /**
     * @var Message
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

    public function __construct(Message $message, $topic, $command)
    {
        $this->message = $message;
        $this->topic = $topic;
        $this->command = $command;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
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
