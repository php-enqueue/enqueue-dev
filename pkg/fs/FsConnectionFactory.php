<?php

namespace Enqueue\Fs;

use Interop\Queue\PsrConnectionFactory;

class FsConnectionFactory implements PsrConnectionFactory
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
     * file://home/foo/enqueue
     * file://home/foo/enqueue?pre_fetch_count=20&chmod=0777
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'file:')
    {
        if (empty($config) || 'file:' === $config) {
            $config = ['path' => sys_get_temp_dir().'/enqueue'];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * {@inheritdoc}
     *
     * @return FsContext
     */
    public function createContext()
    {
        return new FsContext(
            $this->config['path'],
            $this->config['pre_fetch_count'],
            $this->config['chmod'],
            $this->config['polling_interval']
        );
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        if ($dsn && '/' === $dsn[0]) {
            return ['path' => $dsn];
        }

        if (false === strpos($dsn, 'file:')) {
            throw new \LogicException(sprintf('The given DSN "%s" is not supported. Must start with "file:".', $dsn));
        }

        $dsn = substr($dsn, 7);

        $path = parse_url($dsn, PHP_URL_PATH);
        $query = parse_url($dsn, PHP_URL_QUERY);

        if ('/' != $path[0]) {
            throw new \LogicException(sprintf('Failed to parse DSN path "%s". The path must start with "/"', $path));
        }

        if ($query) {
            $config = [];
            parse_str($query, $config);
        }

        if (isset($config['pre_fetch_count'])) {
            $config['pre_fetch_count'] = (int) $config['pre_fetch_count'];
        }

        if (isset($config['chmod'])) {
            $config['chmod'] = intval($config['chmod'], 8);
        }

        $config['path'] = $path;

        return $config;
    }

    private function defaultConfig()
    {
        return [
            'path' => null,
            'pre_fetch_count' => 1,
            'chmod' => 0600,
            'polling_interval' => 100,
        ];
    }
}
