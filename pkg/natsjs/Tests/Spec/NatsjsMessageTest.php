<?php

/*
    Copyright (c) 2024 g41797
    SPDX-License-Identifier: MIT
*/

declare(strict_types=1);

namespace Enqueue\Natsjs\Tests\Spec;

use Enqueue\Natsjs\NatsjsMessage;
use Interop\Queue\Spec\MessageSpec;

class NatsjsMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new NatsjsMessage();
    }
}
