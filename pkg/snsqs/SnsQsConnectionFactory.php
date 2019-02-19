<?php

declare(strict_types=1);

namespace Enqueue\SnsQs;

use Enqueue\Dsn\Dsn;
use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class SnsQsConnectionFactory implements ConnectionFactory
{
    /**
     * @var string|array|null
     */
    private $snsConfig;

    /**
     * @var string|array|null
     */
    private $sqsConfig;

    /**
     * $config = [
     *   'key' => null                AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'secret' => null,            AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'token' => null,             AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'region' => null,            (string, required) Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.
     *   'version' => '2012-11-05',   (string, required) The version of the webservice to utilize
     *   'lazy' => true,              Enable lazy connection (boolean)
     *   'endpoint' => null           (string, default=null) The full URI of the webservice. This is only required when connecting to a custom endpoint e.g. localstack
     * ].
     *
     * or
     *
     * $config = [
     *   'sns_key' => null,           SNS option
     *   'sqs_secret' => null,        SQS option
     *   'token'                      Option for both SNS and SQS
     * ].
     *
     * or
     *
     * snsqs:
     * snsqs:?key=aKey&secret=aSecret&sns_token=aSnsToken&sqs_token=aSqsToken
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'snsqs:')
    {
        if (empty($config)) {
            $this->snsConfig = [];
            $this->sqsConfig = [];
        } elseif (is_string($config)) {
            $this->parseDsn($config);
        } elseif (is_array($config)) {
            if (array_key_exists('dsn', $config)) {
                $this->parseDsn($config['dsn']);
            } else {
                $this->parseOptions($config);
            }
        } else {
            throw new \LogicException(sprintf('The config must be either an array of options, a DSN string, null or instance of %s', AwsSnsClient::class));
        }
    }

    /**
     * @return SnsQsContext
     */
    public function createContext(): Context
    {
        return new SnsQsContext(function() {
            return (new SnsConnectionFactory($this->snsConfig))->createContext();
        }, function() {
            return (new SqsConnectionFactory($this->sqsConfig))->createContext();
        });
    }

    private function parseDsn(string $dsn): void
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('snsqs' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "snsqs"',
                $dsn->getSchemeProtocol()
            ));
        }

        $this->parseOptions($dsn->getQuery());
    }

    private function parseOptions(array $options): void
    {
        // set default options
        foreach ($options as $key => $value) {
            if (false === in_array(substr($key, 0, 4), ['sns_', 'sqs_'])) {
                $this->snsConfig[$key] = $value;
                $this->sqsConfig[$key] = $value;
            }
        }

        // set transport specific options
        foreach ($options as $key => $value) {
            switch (substr($key, 0, 4)) {
                case 'sns_':
                    $this->snsConfig[substr($key, 4)] = $value;
                    break;
                case 'sqs_':
                    $this->sqsConfig[substr($key, 4)] = $value;
                    break;
            }
        }
    }
}
