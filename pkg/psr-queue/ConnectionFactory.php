<?php

namespace Enqueue\Psr;

interface ConnectionFactory
{
    /**
     * @return Context
     */
    public function createContext();
}
