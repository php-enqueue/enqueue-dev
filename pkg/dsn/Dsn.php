<?php

namespace Enqueue\Dsn;

class Dsn
{
    /**
     * @var string
     */
    private $dsn;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string|null
     */
    private $user;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var string|null
     */
    private $host;

    /**
     * @var int|null
     */
    private $port;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var string|null
     */
    private $queryString;

    /**
     * @var array
     */
    private $query;

    /**
     * @var string
     */
    private $schemeProtocol;

    /**
     * @var string[]
     */
    private $schemeExtensions;

    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
        $this->query = [];

        $this->parse($dsn);
    }

    public function __toString(): string
    {
        return $this->dsn;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getSchemeProtocol(): string
    {
        return $this->schemeProtocol;
    }

    /**
     * @return string[]
     */
    public function getSchemeExtensions(): array
    {
        return $this->schemeExtensions;
    }

    public function hasSchemeExtension(string $extension): bool
    {
        return in_array($extension, $this->schemeExtensions, true);
    }

    /**
     * @return null|string
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return null|string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return null|string
     */
    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getQueryParameter(string $name, $default = null)
    {
        return array_key_exists($name, $this->query) ? $this->query[$name] : $default;
    }

    public function toArray()
    {
        return [
            'scheme' => $this->scheme,
            'schemeProtocol' => $this->schemeProtocol,
            'schemeExtensions' => $this->schemeExtensions,
            'user' => $this->user,
            'password' => $this->password,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'queryString' => $this->queryString,
            'query' => $this->query,
        ];
    }

    private function parse(string $dsn): void
    {
        if (false === strpos($dsn, ':')) {
            throw new \LogicException(sprintf('The DSN is invalid. It does not have scheme separator ":".'));
        }

        list($scheme, $dsnWithoutScheme) = explode(':', $dsn, 2);
        if (false == preg_match('/[\w\d+-.]/', $scheme)) {
            throw new \LogicException('The DSN is invalid. Scheme contains illegal symbols.');
        }

        $scheme = strtolower($scheme);

        $schemeParts = explode('+', $scheme);
        $this->scheme = $scheme;
        $this->schemeProtocol = $schemeParts[0];

        unset($schemeParts[0]);
        $this->schemeExtensions = $schemeParts;

        if ($host = parse_url($dsn, PHP_URL_HOST)) {
            $this->host = $host;
        }

        if ($port = parse_url($dsn, PHP_URL_PORT)) {
            $this->port = (int) $port;
        }

        if ($user = parse_url($dsn, PHP_URL_USER)) {
            $this->user = $user;
        }

        if ($password = parse_url($dsn, PHP_URL_PASS)) {
            $this->password = $password;
        }

        if ($path = parse_url($dsn, PHP_URL_PATH)) {
            $this->path = $path;
        }

        if ($queryString = parse_url($dsn, PHP_URL_QUERY)) {
            $this->queryString = $queryString;

            $query = [];
            parse_str($queryString, $query);
            $this->query = $query;
        }
    }
}
