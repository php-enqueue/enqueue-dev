<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class AzureStorageDestination implements Queue, Topic
{
    /**
     * @var string
     */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQueueName(): string
    {
        return $this->name;
    }

    public function getTopicName(): string
    {
        return $this->name;
    }
}