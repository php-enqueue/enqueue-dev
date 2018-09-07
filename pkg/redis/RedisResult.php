<?php

declare(strict_types=1);

namespace Enqueue\Redis;

class RedisResult
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $message;

    public function __construct(string $key, string $message)
    {
        $this->key = $key;
        $this->message = $message;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
