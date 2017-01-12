<?php

namespace Enqueue\Fs;

use Enqueue\Psr\Queue;
use Enqueue\Psr\Topic;

class FsDestination implements Queue, Topic
{
    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * @param \SplFileInfo $file
     */
    public function __construct(\SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * @return \SplFileInfo
     */
    public function getFileInfo()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->file->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName()
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicName()
    {
        return $this->getName();
    }
}
