<?php
namespace Enqueue\Fs\Tests\Spec;

use Enqueue\Fs\FsMessage;
use Enqueue\Psr\Spec\PsrMessageSpec;

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