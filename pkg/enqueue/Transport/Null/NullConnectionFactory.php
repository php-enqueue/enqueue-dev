<?php
namespace Enqueue\Transport\Null;

use Enqueue\Psr\ConnectionFactory;

class NullConnectionFactory implements ConnectionFactory
{
    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return new NullContext();
    }
}
