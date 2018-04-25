<?php

namespace Enqueue\Fs\Tests\Spec;

use Enqueue\Fs\FsMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class FsMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new FsMessage();
    }
}
