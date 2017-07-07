<?php

namespace Enqueue\Sqs;

use Aws\Sqs\SqsClient;
use Interop\Queue\PsrConnectionFactory;

class SqsConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var SqsClient
     */
    private $client;

    /**
     * $config = [
     *   'key' => null              - AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'secret' => null,          - AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'token' => null,           - AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'region' => null,          - (string, required) Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.
     *   'retries' => 3,            - (int, default=int(3)) Configures the maximum number of allowed retries for a client (pass 0 to disable retries).
     *   'version' => '2012-11-05', - (string, required) The version of the webservice to utilize
     *   'lazy' => true,            - Enable lazy connection (boolean)
     * ].
     *
     * @param $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_replace([
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'retries' => 3,
            'version' => '2012-11-05',
            'lazy' => true,
        ], $config);
    }

    /**
     * {@inheritdoc}
     *
     * @return SqsContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            return new SqsContext(function () {
                return $this->establishConnection();
            });
        }

        return new SqsContext($this->establishConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }

    /**
     * @return SqsClient
     */
    private function establishConnection()
    {
        if ($this->client) {
            return $this->client;
        }

        $config = [
            'version' => $this->config['version'],
            'retries' => $this->config['retries'],
            'region' => $this->config['region'],
        ];

        if ($this->config['key'] && $this->config['secret']) {
            $config['credentials'] = [
                'key' => $this->config['key'],
                'secret' => $this->config['secret'],
            ];

            if ($this->config['token']) {
                $config['credentials']['token'] = $this->config['token'];
            }
        }

        $this->client = new SqsClient($config);

        return $this->client;
    }
}
