<?php

declare(strict_types=1);

namespace Enqueue\AmqpTools;

use Enqueue\Dsn\Dsn;

/**
 * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost with default credentials.
 *
 * 1. The config could be an array with next options:
 *   host - The host to connect too. Note: Max 1024 characters
 *   port - Port on the host
 *   vhost - The virtual host on the host. Note: Max 128 characters
 *   user - The user name to use. Note: Max 128 characters
 *   pass - Password. Note: Max 128 characters
 *   read_timeout - Timeout in for income activity. Note: 0 or greater seconds. May be fractional
 *   write_timeout - Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional
 *   connection_timeout - Connection timeout. Note: 0 or greater seconds. May be fractional
 *   heartbeat - how often to send heartbeat. 0 means off
 *   persisted - bool, Whether it use single persisted connection or open a new one for every context
 *   lazy - the connection will be performed as later as possible, if the option set to true
 *   qos_prefetch_size - The server will send a message in advance if it is equal to or smaller in size than the available prefetch size. May be set to zero, meaning "no specific limit"
 *   qos_prefetch_count - Specifies a prefetch window in terms of whole messages
 *   qos_global - If "false" the QoS settings apply to the current channel only. If this field is "true", they are applied to the entire connection.
 *   ssl_on - Should be true if you want to use secure connections. False by default
 *   ssl_verify - This option determines whether ssl client verifies that the server cert is for the server it is known as. True by default.
 *   ssl_cacert - Location of Certificate Authority file on local filesystem which should be used with the verify_peer context option to authenticate the identity of the remote peer. A string.
 *   ssl_cert - Path to local certificate file on filesystem. It must be a PEM encoded file which contains your certificate and private key. A string
 *   ssl_key - Path to local private key file on filesystem in case of separate files for certificate (local_cert) and private key. A string.
 *   ssl_passphrase - Passphrase with which your local_cert file was encoded. A string
 *
 * 2. null - in this case it tries to connect to localhost with default settings
 * 3. amqp: same as 2.
 * 4. amqp://user:pass@host:10000/vhost?lazy=true&persisted=false&read_timeout=2
 * 5. amqp+foo: - the scheme driver could be used. (make sure you added it to the list of supported schemes)
 * 6. amqps: - secure connection to localhost
 * 7. amqp+tls: - secure connection
 * 8. amqp+rabbitmq: - if you connect to RabbitMQ server
 *
 * @see https://www.rabbitmq.com/uri-spec.html
 */
class ConnectionConfig
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var array|string|null
     */
    private $inputConfig;

    /**
     * @var array
     */
    private $defaultConfig;

    /**
     * @var string[]
     */
    private $supportedSchemes;

    /**
     * @var array
     */
    private $schemeExtensions = [];

    /**
     * @param array|string|null $config
     */
    public function __construct($config = null)
    {
        $this->inputConfig = $config;

        $this->supportedSchemes = [];
        $this->defaultConfig = [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'read_timeout' => 3.,
            'write_timeout' => 3.,
            'connection_timeout' => 3.,
            'heartbeat' => 0,
            'persisted' => false,
            'lazy' => true,
            'qos_global' => false,
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'ssl_on' => false,
            'ssl_verify' => true,
            'ssl_cacert' => '',
            'ssl_cert' => '',
            'ssl_key' => '',
            'ssl_passphrase' => '',
        ];
        $this->schemeExtensions = [];

        $this->addSupportedScheme('amqp');
        $this->addSupportedScheme('amqps');
    }

    /**
     * @param string[] $extensions
     */
    public function addSupportedScheme(string $schema): self
    {
        $this->supportedSchemes[] = $schema;
        $this->supportedSchemes = array_unique($this->supportedSchemes);

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function addDefaultOption($name, $value)
    {
        $this->defaultConfig[$name] = $value;

        return $this;
    }

    /**
     * @return self
     */
    public function parse()
    {
        if (empty($this->inputConfig) || in_array($this->inputConfig, $this->supportedSchemes, true)) {
            $config = [];
        } elseif (is_string($this->inputConfig)) {
            $config = $this->parseDsn($this->inputConfig);
        } elseif (is_array($this->inputConfig)) {
            $config = $this->inputConfig;
            if (array_key_exists('dsn', $config)) {
                $dsn = $config['dsn'];
                unset($config['dsn']);

                if ($dsn) {
                    $config = array_replace($config, $this->parseDsn($dsn));
                }
            }
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $config = array_replace($this->defaultConfig, $config);
        $config['host'] = (string) $config['host'];
        $config['port'] = (int) ($config['port']);
        $config['user'] = (string) $config['user'];
        $config['pass'] = (string) $config['pass'];
        $config['read_timeout'] = max((float) ($config['read_timeout']), 0);
        $config['write_timeout'] = max((float) ($config['write_timeout']), 0);
        $config['connection_timeout'] = max((float) ($config['connection_timeout']), 0);
        $config['heartbeat'] = max((float) ($config['heartbeat']), 0);
        $config['persisted'] = !empty($config['persisted']);
        $config['lazy'] = !empty($config['lazy']);
        $config['qos_global'] = !empty($config['qos_global']);
        $config['qos_prefetch_count'] = max((int) ($config['qos_prefetch_count']), 0);
        $config['qos_prefetch_size'] = max((int) ($config['qos_prefetch_size']), 0);
        $config['ssl_on'] = !empty($config['ssl_on']);
        $config['ssl_verify'] = !empty($config['ssl_verify']);
        $config['ssl_cacert'] = (string) $config['ssl_cacert'];
        $config['ssl_cert'] = (string) $config['ssl_cert'];
        $config['ssl_key'] = (string) $config['ssl_key'];
        $config['ssl_passphrase'] = (string) $config['ssl_passphrase'];

        $this->config = $config;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSchemeExtensions(): array
    {
        return $this->schemeExtensions;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->getOption('host');
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->getOption('port');
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->getOption('user');
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->getOption('pass');
    }

    /**
     * @return string
     */
    public function getVHost()
    {
        return $this->getOption('vhost');
    }

    /**
     * @return int
     */
    public function getReadTimeout()
    {
        return $this->getOption('read_timeout');
    }

    /**
     * @return int
     */
    public function getWriteTimeout()
    {
        return $this->getOption('write_timeout');
    }

    /**
     * @return int
     */
    public function getConnectionTimeout()
    {
        return $this->getOption('connection_timeout');
    }

    /**
     * @return int
     */
    public function getHeartbeat()
    {
        return $this->getOption('heartbeat');
    }

    /**
     * @return bool
     */
    public function isPersisted()
    {
        return $this->getOption('persisted');
    }

    /**
     * @return bool
     */
    public function isLazy()
    {
        return $this->getOption('lazy');
    }

    /**
     * @return bool
     */
    public function isQosGlobal()
    {
        return $this->getOption('qos_global');
    }

    /**
     * @return int
     */
    public function getQosPrefetchSize()
    {
        return $this->getOption('qos_prefetch_size');
    }

    /**
     * @return int
     */
    public function getQosPrefetchCount()
    {
        return $this->getOption('qos_prefetch_count');
    }

    /**
     * @return bool
     */
    public function isSslOn()
    {
        return $this->getOption('ssl_on');
    }

    /**
     * @return bool
     */
    public function isSslVerify()
    {
        return $this->getOption('ssl_verify');
    }

    /**
     * @return string
     */
    public function getSslCaCert()
    {
        return $this->getOption('ssl_cacert');
    }

    /**
     * @return string
     */
    public function getSslCert()
    {
        return $this->getOption('ssl_cert');
    }

    /**
     * @return string
     */
    public function getSslKey()
    {
        return $this->getOption('ssl_key');
    }

    /**
     * @return string
     */
    public function getSslPassPhrase()
    {
        return $this->getOption('ssl_passphrase');
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        $config = $this->getConfig();

        return array_key_exists($name, $config) ? $config[$name] : $default;
    }

    /**
     * @throws \LogicException if the input config has not been parsed
     *
     * @return array
     */
    public function getConfig()
    {
        if (null === $this->config) {
            throw new \LogicException('The config has not been parsed.');
        }

        return $this->config;
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        $dsn = Dsn::parseFirst($dsn);

        $supportedSchemes = $this->supportedSchemes;
        if (false == in_array($dsn->getSchemeProtocol(), $supportedSchemes, true)) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be one of "%s".',
                $dsn->getSchemeProtocol(),
                implode('", "', $supportedSchemes)
            ));
        }

        $sslOn = false;
        $isAmqps = 'amqps' === $dsn->getSchemeProtocol();
        $isTls = in_array('tls', $dsn->getSchemeExtensions(), true);
        $isSsl = in_array('ssl', $dsn->getSchemeExtensions(), true);
        if ($isAmqps || $isTls || $isSsl) {
            $sslOn = true;
        }

        $this->schemeExtensions = $dsn->getSchemeExtensions();

        $config = array_filter(array_replace($dsn->getQuery(), [
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'user' => $dsn->getUser(),
            'pass' => $dsn->getPassword(),
            'vhost' => null !== ($path = $dsn->getPath()) ?
                (0 === strpos($path, '/') ? substr($path, 1) : $path)
                : null,
            'read_timeout' => $dsn->getFloat('read_timeout'),
            'write_timeout' => $dsn->getFloat('write_timeout'),
            'connection_timeout' => $dsn->getFloat('connection_timeout'),
            'heartbeat' => $dsn->getFloat('heartbeat'),
            'persisted' => $dsn->getBool('persisted'),
            'lazy' => $dsn->getBool('lazy'),
            'qos_global' => $dsn->getBool('qos_global'),
            'qos_prefetch_size' => $dsn->getDecimal('qos_prefetch_size'),
            'qos_prefetch_count' => $dsn->getDecimal('qos_prefetch_count'),
            'ssl_on' => $sslOn,
            'ssl_verify' => $dsn->getBool('ssl_verify'),
            'ssl_cacert' => $dsn->getString('ssl_cacert'),
            'ssl_cert' => $dsn->getString('ssl_cert'),
            'ssl_key' => $dsn->getString('ssl_key'),
            'ssl_passphrase' => $dsn->getString('ssl_passphrase'),
        ]), function ($value) { return null !== $value; });

        return array_map(function ($value) {
            return is_string($value) ? rawurldecode($value) : $value;
        }, $config);
    }
}
