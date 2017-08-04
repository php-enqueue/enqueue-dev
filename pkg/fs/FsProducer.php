<?php

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

    /**
     * @param FsContext $context
     */
    public function __construct(FsContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @param FsDestination $destination
     * @param FsMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, FsDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, FsMessage::class);

        $this->context->workWithFile($destination, 'a+', function (FsDestination $destination, $file) use ($message) {
            $fileInfo = $destination->getFileInfo();
            if ($fileInfo instanceof TempFile && false == file_exists($fileInfo)) {
                return;
            }

            if (null !== $this->timeToLive) {
                $message->setHeader('x-expire-at', microtime(true) + ($this->timeToLive / 1000));
            }

            $rawMessage = '|'.json_encode($message);

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

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        if (null === $deliveryDelay) {
            return;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        if (null === $priority) {
            return;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeToLive($timeToLive)
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeToLive()
    {
        return null;
    }
}
