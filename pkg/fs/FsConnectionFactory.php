<?php

declare(strict_types=1);

namespace Enqueue\Fs;

use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class FsConnectionFactory implements ConnectionFactory
{
    /**
     * @var string
     */
    private $config;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to store files in /tmp/enqueue folder.
     *
     * [
     *   'path' => 'the directory where all queue\topic files remain. For example /home/foo/enqueue',
     *   'pre_fetch_count' => 'Integer. Defines how many messages to fetch from the file.',
     *   'chmod' => 'Defines a mode the files are created with',
     *   'polling_interval' => 'How often query for new messages, default 100 (milliseconds)',
     * ]
     *
     * or
     *
     * file: - create queue files in tmp dir.
     * file:///home/foo/enqueue
     * file:///home/foo/enqueue?pre_fetch_count=20&chmod=0777
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'file:')
    {
        if (empty($config) || 'file:' === $config) {
            $config = $this->parseDsn('file://'.sys_get_temp_dir().'/enqueue');
        } elseif (is_string($config)) {
            if ('/' === $config[0]) {
                $config = $this->parseDsn('file://'.$config);
            } else {
                $config = $this->parseDsn($config);
            }
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);

        if (empty($this->config['path'])) {
            throw new \LogicException('The path option must be set.');
        }
    }

    /**
     * @return FsContext
     */
    public function createContext(): Context
    {
        return new FsContext(
            $this->config['path'],
            $this->config['pre_fetch_count'],
            $this->config['chmod'],
            $this->config['polling_interval']
        );
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        $supportedSchemes = ['file'];
        if (false == in_array($dsn->getSchemeProtocol(), $supportedSchemes, true)) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be one of "%s"',
                $dsn->getSchemeProtocol(),
                implode('", "', $supportedSchemes)
            ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'path' => $dsn->getPath(),
            'pre_fetch_count' => $dsn->getDecimal('pre_fetch_count'),
            'chmod' => $dsn->getOctal('chmod'),
            'polling_interval' => $dsn->getDecimal('polling_interval'),
        ]), function ($value) { return null !== $value; });
    }

    private function defaultConfig(): array
    {
        return [
            'path' => null,
            'pre_fetch_count' => 1,
            'chmod' => 0600,
            'polling_interval' => 100,
        ];
    }
}
