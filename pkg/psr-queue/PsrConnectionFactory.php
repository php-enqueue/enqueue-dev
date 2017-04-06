<?php

namespace Enqueue\Psr;

interface PsrConnectionFactory
{
    /**
     * @return Context
     */
    public function createContext();
}
