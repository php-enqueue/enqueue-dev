<?php

namespace Enqueue\Fs\Tests\Spec;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Interop\Queue\Spec\SendAndReceiveTimeToLiveMessagesFromQueueSpec;

class FsSendAndReceiveTimeToLiveMessagesFromQueueTest extends SendAndReceiveTimeToLiveMessagesFromQueueSpec
{
    /**
     * {@inheritdoc}
     *
     * @return FsContext
     */
    protected function createContext()
    {
        return (new FsConnectionFactory())->createContext();
    }
}
