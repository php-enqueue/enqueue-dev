<?php

namespace Enqueue\Fs;

use Enqueue\Psr\ConnectionFactory;

class FsConnectionFactory implements ConnectionFactory
{
    /**
     * @var string
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace([
            'store_dir' => null,
            'pre_fetch_count' => 1,
            'chmod' => 0600,
        ], $config);
    }

    /**
     * {@inheritdoc}
     *
     * @return FsContext
     */
    public function createContext()
    {
        return new FsContext($this->config['store_dir'], $this->config['pre_fetch_count'], $this->config['chmod']);
    }
}
