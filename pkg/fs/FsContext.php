<?php

namespace Enqueue\Fs;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrQueue;
use Makasim\File\TempFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\LockHandler;

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
     * @var LockHandler[]
     */
    private $lockHandlers;

    /**
     * @param string $storeDir
     * @param int    $preFetchCount
     * @param int    $chmod
     */
    public function __construct($storeDir, $preFetchCount, $chmod)
    {
        $fs = new Filesystem();
        $fs->mkdir($storeDir);

        $this->storeDir = $storeDir;
        $this->preFetchCount = $preFetchCount;
        $this->chmod = $chmod;

        $this->lockHandlers = [];
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
            $lockHandler = $this->getLockHandler($destination);

            if (false == $lockHandler->lock(true)) {
                throw new \LogicException(sprintf('Cannot obtain the lock for destination %s', $destination->getName()));
            }

            return call_user_func($callback, $destination, $file);
        } finally {
            if (isset($file)) {
                fclose($file);
            }
            if (isset($lockHandler)) {
                $lockHandler->release();
            }

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

        return new FsConsumer($this, $destination, $this->preFetchCount);
    }

    public function close()
    {
        foreach ($this->lockHandlers as $lockHandler) {
            $lockHandler->release();
        }

        $this->lockHandlers = [];
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

    /**
     * @param FsDestination $destination
     *
     * @return LockHandler
     */
    private function getLockHandler(FsDestination $destination)
    {
        if (false == isset($this->lockHandlers[$destination->getName()])) {
            $this->lockHandlers[$destination->getName()] = new LockHandler($destination->getName(), $this->storeDir);
        }

        return $this->lockHandlers[$destination->getName()];
    }
}
