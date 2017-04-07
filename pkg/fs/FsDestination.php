<?php

namespace Enqueue\Fs;

use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;

class FsDestination implements PsrQueue, PsrTopic
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
