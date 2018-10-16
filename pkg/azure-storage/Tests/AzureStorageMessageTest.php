<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage\Tests;

use Enqueue\AzureStorage\AzureStorageMessage;
use Interop\Queue\Spec\MessageSpec;

class AzureStorageMessageTest extends MessageSpec
{
    public function createMessage()
    {
        return new AzureStorageMessage();
    }
}