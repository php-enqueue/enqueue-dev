<?php

namespace Enqueue\Fs;

use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class FsDestination implements PsrQueue, PsrTopic
{
    /**
     * @var \SplFileInfo
     */
    private $file;

    public function __construct(\SplFileInfo $file)
    {
        $this->file = $file;
    }

    public function getFileInfo(): \SplFileInfo
    {
        return $this->file;
    }

    public function getName(): string
    {
        return $this->file->getFilename();
    }

    public function getQueueName(): string
    {
        return $this->file->getFilename();
    }

    public function getTopicName(): string
    {
        return $this->file->getFilename();
    }
}
