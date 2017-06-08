<?php

namespace Enqueue\Fs;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProducer;
use Enqueue\Util\JSON;
use Makasim\File\TempFile;

class FsProducer implements PsrProducer
{
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

            $rawMessage = '|'.JSON::encode($message);
            $rawMessage = str_repeat(' ', 64 - (strlen($rawMessage) % 64)).$rawMessage;

            fwrite($file, $rawMessage);
        });
    }
}
