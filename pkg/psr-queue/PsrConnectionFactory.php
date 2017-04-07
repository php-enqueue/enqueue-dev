<?php

namespace Enqueue\Psr;

interface PsrConnectionFactory
{
    /**
     * @return PsrContext
     */
    public function createContext();
}
