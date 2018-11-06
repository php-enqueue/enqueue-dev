<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

use Enqueue\Dsn\Dsn;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

class WampStorage implements StatsStorage
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Serializer
     */
    private $serialiser;

    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var Stats
     */
    private $stats;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to Thruway localhost.
     *
     * $config = [
     *   'dsn'                 => 'wamp://127.0.0.1:9090',
     *   'host'                => '127.0.0.1',
     *   'port'                => '9090',
     *   'topic'               => 'stats',
     *   'max_retries'         => 15,
     *   'initial_retry_delay' => 1.5,
     *   'max_retry_delay'     => 300,
     *   'retry_delay_growth'  => 1.5,
     * ]
     *
     * or
     *
     * wamp://127.0.0.1:9090?max_retries=10
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'wamp:')
    {
        if (false == class_exists(Client::class) || false == class_exists(PawlTransportProvider::class)) {
            throw new \LogicException('Seems client libraries are not installed. Please install "thruway/client" and "thruway/pawl-transport"');
        }

        if (empty($config)) {
            $config = $this->parseDsn('wamp:');
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            $config = empty($config['dsn']) ? $config : $this->parseDsn($config['dsn']);
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $config = array_replace([
            'host' => '127.0.0.1',
            'port' => '9090',
            'topic' => 'stats',
            'max_retries' => 15,
            'initial_retry_delay' => 1.5,
            'max_retry_delay' => 300,
            'retry_delay_growth' => 1.5,
        ], $config);

        $this->config = $config;

        $this->serialiser = new JsonSerializer();
    }

    public function pushConsumerStats(ConsumerStats $stats): void
    {
        $this->push($stats);
    }

    public function pushConsumedMessageStats(ConsumedMessageStats $stats): void
    {
        $this->push($stats);
    }

    public function pushSentMessageStats(SentMessageStats $stats): void
    {
        $this->push($stats);
    }

    private function push(Stats $stats)
    {
        $init = false;
        $this->stats = $stats;

        if (null === $this->client) {
            $init = true;

            $this->client = $this->createClient();
            $this->client->setAttemptRetry(true);
            $this->client->on('open', function (ClientSession $session) {
                $this->session = $session;

                $this->doSendMessageIfPossible();
            });

            $this->client->on('close', function () {
                if ($this->session === $this->client->getSession()) {
                    $this->session = null;
                }
            });

            $this->client->on('error', function () {
                if ($this->session === $this->client->getSession()) {
                    $this->session = null;
                }
            });

            $this->client->on('do-send', function (Stats $stats) {
                $onFinish = function () {
                    $this->client->emit('do-stop');
                };

                $payload = $this->serialiser->toString($stats);

                $this->session->publish('stats', [$payload], [], ['acknowledge' => true])
                    ->then($onFinish, $onFinish);
            });

            $this->client->on('do-stop', function () {
                $this->client->getLoop()->stop();
            });
        }

        $this->client->getLoop()->futureTick(function () {
            $this->doSendMessageIfPossible();
        });

        if ($init) {
            $this->client->start(false);
        }

        $this->client->getLoop()->run();
    }

    private function doSendMessageIfPossible()
    {
        if (null === $this->session) {
            return;
        }

        if (null === $this->stats) {
            return;
        }

        $stats = $this->stats;

        $this->stats = null;

        $this->client->emit('do-send', [$stats]);
    }

    private function createClient(): Client
    {
        $uri = sprintf('ws://%s:%s', $this->config['host'], $this->config['port']);

        $client = new Client('realm1');
        $client->addTransportProvider(new PawlTransportProvider($uri));
        $client->setReconnectOptions([
            'max_retries' => $this->config['max_retries'],
            'initial_retry_delay' => $this->config['initial_retry_delay'],
            'max_retry_delay' => $this->config['max_retry_delay'],
            'retry_delay_growth' => $this->config['retry_delay_growth'],
        ]);

        return $client;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = new Dsn($dsn);

        if (false === in_array($dsn->getSchemeProtocol(), ['wamp', 'ws'], true)) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "wamp"',
                $dsn->getSchemeProtocol()
            ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'topic' => $dsn->getQueryParameter('topic'),
            'max_retries' => $dsn->getInt('max_retries'),
            'initial_retry_delay' => $dsn->getFloat('initial_retry_delay'),
            'max_retry_delay' => $dsn->getInt('max_retry_delay'),
            'retry_delay_growth' => $dsn->getFloat('retry_delay_growth'),
        ]), function ($value) { return null !== $value; });
    }
}
