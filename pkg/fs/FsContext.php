<?php

declare(strict_types=1);

namespace Enqueue\Fs;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;
use Makasim\File\TempFile;
use Symfony\Component\Filesystem\Filesystem;

class FsContext implements Context
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
     * @var int
     */
    private $pollingInterval;

    /**
     * @var Lock
     */
    private $lock;

    public function __construct(string $storeDir, int $preFetchCount, int $chmod, int $pollingInterval)
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
     * @return FsMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new FsMessage($body, $properties, $headers);
    }

    /**
     * @return FsDestination
     */
    public function createTopic(string $topicName): Topic
    {
        return $this->createQueue($topicName);
    }

    /**
     * @return FsDestination
     */
    public function createQueue(string $queueName): Queue
    {
        return new FsDestination(new \SplFileInfo($this->getStoreDir().'/'.$queueName));
    }

    public function declareDestination(FsDestination $destination): void
    {
        //InvalidDestinationException::assertDestinationInstanceOf($destination, FsDestination::class);

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            if (false == file_exists((string) $destination->getFileInfo())) {
                touch((string) $destination->getFileInfo());
                chmod((string) $destination->getFileInfo(), $this->chmod);
            }
        } finally {
            restore_error_handler();
        }
    }

    public function workWithFile(FsDestination $destination, string $mode, callable $callback)
    {
        $this->declareDestination($destination);

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_ALL & ~E_USER_DEPRECATED);

        try {
            $file = fopen((string) $destination->getFileInfo(), $mode);
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
     * @return FsDestination
     */
    public function createTemporaryQueue(): Queue
    {
        return new FsDestination(
            new TempFile($this->getStoreDir().'/'.uniqid('tmp-q-', true))
        );
    }

    /**
     * @return FsProducer
     */
    public function createProducer(): Producer
    {
        return new FsProducer($this);
    }

    /**
     * @param FsDestination $destination
     *
     * @return FsConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, FsDestination::class);

        $consumer = new FsConsumer($this, $destination, $this->preFetchCount);

        if ($this->pollingInterval) {
            $consumer->setPollingInterval($this->pollingInterval);
        }

        return $consumer;
    }

    public function close(): void
    {
        $this->lock->releaseAll();
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @param FsDestination $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, FsDestination::class);

        $this->workWithFile($queue, 'c', function (FsDestination $destination, $file) {
            ftruncate($file, 0);
        });
    }

    public function getPreFetchCount(): int
    {
        return $this->preFetchCount;
    }

    public function setPreFetchCount(int $preFetchCount): void
    {
        $this->preFetchCount = $preFetchCount;
    }

    private function getStoreDir(): string
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
