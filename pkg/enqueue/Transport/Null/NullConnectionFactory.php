<?php

namespace Enqueue\Transport\Null;

use Enqueue\Psr\PsrConnectionFactory;

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
