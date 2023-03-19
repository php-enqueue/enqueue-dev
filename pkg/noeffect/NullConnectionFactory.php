<?php

declare(strict_types=1);

namespace Enqueue\NoEffect;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class NullConnectionFactory implements ConnectionFactory
{
    /**
     * @return NullContext
     */
    public function createContext(): Context
    {
        return new NullContext();
    }
}
