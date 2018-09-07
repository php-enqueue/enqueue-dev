<?php

declare(strict_types=1);

namespace Enqueue\AmqpBunny;

use Bunny\Client;
use Bunny\Exception\ClientException;

class BunnyClient extends Client
{
    public function __destruct()
    {
        try {
            parent::__destruct();
        } catch (ClientException $e) {
            if ('Broken pipe or closed connection.' !== $e->getMessage()) {
                throw $e;
            }
        }
    }
}
