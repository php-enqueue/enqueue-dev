<?php

namespace Enqueue\Fs;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrQueue;
use Makasim\File\TempFile;
use Symfony\Component\Filesystem\Filesystem;

class FsContext implements PsrContext
{
    /**
     * @var string
     */
    private $storeDir;

    /**
     * @var int
     */
    private $preFetchCount;

    /**
     * @var int
     */
    private $chmod;

    /**
     * @var null
     */
    private $pollingInterval;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @param string $storeDir
     * @param int    $preFetchCount
     * @param int    $chmod
     * @param null   $pollingInterval
     */
    public function __construct($storeDir, $preFetchCount, $chmod, $pollingInterval = null)
    {
        $fs = new Filesystem();
        $fs->mkdir($storeDir);

        $this->storeDir = $storeDir;
        $this->preFetchCount = $preFetchCount;
        $this->chmod = $chmod;
        $this->pollingInterval = $pollingInterval;

        $this->lock = new LegacyFilesystemLock();
    }

    /**
     * {@inheritdoc}
     *
     * @return FsMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new FsMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @return FsDestination
     */
    public function createTopic($topicName)
    {
        return $this->createQueue($topicName);
    }

    /**
     * {@inheritdoc}
     *
     * @return FsDestination
     */
    public function createQueue($queueName)
    {
        return new FsDestination(new \SplFileInfo($this->getStoreDir().'/'.$queueName));
    }

    /**
     * @param PsrDestination|FsDestination $destination
     */
    public function declareDestination(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, FsDestination::class);

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            if (false == file_exists($destination->getFileInfo())) {
                touch($destination->getFileInfo());
                chmod($destination->getFileInfo(), $this->chmod);
            }
        } finally {
            restore_error_handler();
        }
    }

    public function workWithFile(FsDestination $destination, $mode, callable $callback)
    {
        $this->declareDestination($destination);

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $file = fopen($destination->getFileInfo(), $mode);
            $this->lock->lock($destination);

            return call_user_func($callback, $destination, $file);
        } finally {
            if (isset($file)) {
                fclose($file);
            }
            $this->lock->release($destination);

            restore_error_handler();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return FsDestination
     */
    public function createTemporaryQueue()
    {
        return new FsDestination(new TempFile($this->getStoreDir().'/'.uniqid('tmp-q-', true)));
    }

    /**
     * {@inheritdoc}
     *
     * @return FsProducer
     */
    public function createProducer()
    {
        return new FsProducer($this);
    }

    /**
     * {@inheritdoc}
     *
     * @param FsDestination|PsrDestination $destination
     *
     * @return FsConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, FsDestination::class);

        $consumer = new FsConsumer($this, $destination, $this->preFetchCount);

        if ($this->pollingInterval) {
            $consumer->setPollingInterval($this->pollingInterval);
        }

        return $consumer;
    }

    public function close()
    {
        $this->lock->releaseAll();
    }

    /**
     * @param PsrQueue|FsDestination $queue
     */
    public function purge(PsrQueue $queue)
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, FsDestination::class);

        $this->workWithFile($queue, 'c', function (FsDestination $destination, $file) {
            ftruncate($file, 0);
        });
    }

    /**
     * @return int
     */
    public function getPreFetchCount()
    {
        return $this->preFetchCount;
    }

    /**
     * @param int $preFetchCount
     */
    public function setPreFetchCount($preFetchCount)
    {
        $this->preFetchCount = $preFetchCount;
    }

    /**
     * @return string
     */
    private function getStoreDir()
    {
        if (false == is_dir($this->storeDir)) {
            throw new \LogicException(sprintf('The directory %s does not exist', $this->storeDir));
        }

        if (false == is_writable($this->storeDir)) {
            throw new \LogicException(sprintf('The directory %s is not writable', $this->storeDir));
        }

        return $this->storeDir;
    }
}
