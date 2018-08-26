<?php

declare(strict_types=1);

namespace Enqueue\Fs;

use Interop\Queue\DeliveryDelayNotSupportedException;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PriorityNotSupportedException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Makasim\File\TempFile;

class FsProducer implements PsrProducer
{
    /**
     * @var float|int|null
     */
    private $timeToLive;

    /**
     * @var FsContext
     */
    private $context;

    public function __construct(FsContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param FsDestination $destination
     * @param FsMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, FsDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, FsMessage::class);

        $this->context->workWithFile($destination, 'a+', function (FsDestination $destination, $file) use ($message) {
            $fileInfo = $destination->getFileInfo();
            if ($fileInfo instanceof TempFile && false == file_exists((string) $fileInfo)) {
                return;
            }

            if (null !== $this->timeToLive) {
                $message->setHeader('x-expire-at', microtime(true) + ($this->timeToLive / 1000));
            }

            $rawMessage = json_encode($message);
            $rawMessage = str_replace('|{', '\|\{', $rawMessage);
            $rawMessage = '|'.$rawMessage;

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not encode value into json. Error %s and message %s',
                    json_last_error(),
                    json_last_error_msg()
                ));
            }

            $rawMessage = str_repeat(' ', 64 - (strlen($rawMessage) % 64)).$rawMessage;

            fwrite($file, $rawMessage);
        });
    }

    public function setDeliveryDelay(int $deliveryDelay = null): PsrProducer
    {
        if (null === $deliveryDelay) {
            return $this;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    public function setPriority(int $priority = null): PsrProducer
    {
        if (null === $priority) {
            return $this;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPriority(): ?int
    {
        return null;
    }

    public function setTimeToLive(int $timeToLive = null): PsrProducer
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }
}
