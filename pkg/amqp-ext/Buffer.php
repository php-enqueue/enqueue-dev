<?php

namespace Enqueue\AmqpExt;

class Buffer
{
    /**
     * @var array ['aTag' => [AmqpMessage, AmqpMessage ...]]
     */
    private $buffer;

    public function __construct()
    {
        $this->buffer = [];
    }

    /**
     * @param string      $consumerTag
     * @param AmqpMessage $message
     */
    public function push($consumerTag, AmqpMessage $message)
    {
        if (false == array_key_exists($consumerTag, $this->buffer)) {
            $this->buffer[$consumerTag] = [];
        }

        $this->buffer[$consumerTag][] = $message;
    }

    /**
     * @param string $consumerTag
     *
     * @return AmqpMessage|null
     */
    public function pop($consumerTag)
    {
        if (false == empty($this->buffer[$consumerTag])) {
            return array_shift($this->buffer[$consumerTag]);
        }
    }
}
