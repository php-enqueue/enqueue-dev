<?php

namespace Enqueue\Null;

use Interop\Queue\PsrConnectionFactory;

class NullConnectionFactory implements PsrConnectionFactory
{
    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return new NullContext();
    }
}
