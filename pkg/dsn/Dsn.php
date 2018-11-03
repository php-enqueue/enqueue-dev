<?php

declare(strict_types=1);

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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getQueryParameter(string $name, string $default = null): ?string
    {
        return array_key_exists($name, $this->query) ? $this->query[$name] : $default;
    }

    public function getInt(string $name, int $default = null): ?int
    {
        $value = $this->getQueryParameter($name);
        if (null === $value) {
            return $default;
        }

        if (false == preg_match('/^[\+\-]?[0-9]*$/', $value)) {
            throw InvalidQueryParameterTypeException::create($name, 'integer');
        }

        return (int) $value;
    }

    public function getOctal(string $name, int $default = null): ?int
    {
        $value = $this->getQueryParameter($name);
        if (null === $value) {
            return $default;
        }

        if (false == preg_match('/^0[\+\-]?[0-7]*$/', $value)) {
            throw InvalidQueryParameterTypeException::create($name, 'integer');
        }

        return intval($value, 8);
    }

    public function getFloat(string $name, float $default = null): ?float
    {
        $value = $this->getQueryParameter($name);
        if (null === $value) {
            return $default;
        }

        if (false == is_numeric($value)) {
            throw InvalidQueryParameterTypeException::create($name, 'float');
        }

        return (float) $value;
    }

    public function getBool(string $name, bool $default = null): ?bool
    {
        $value = $this->getQueryParameter($name);
        if (null === $value) {
            return $default;
        }

        if (in_array($value, ['', '0', 'false'], true)) {
            return false;
        }

        if (in_array($value, ['1', 'true'], true)) {
            return true;
        }

        throw InvalidQueryParameterTypeException::create($name, 'bool');
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

        $scheme = strtolower($scheme);
        if (false == preg_match('/^[a-z\d+-.]*$/', $scheme)) {
            throw new \LogicException('The DSN is invalid. Scheme contains illegal symbols.');
        }

        $schemeParts = explode('+', $scheme);
        $this->scheme = $scheme;
        $this->schemeProtocol = $schemeParts[0];

        unset($schemeParts[0]);
        $this->schemeExtensions = array_values($schemeParts);

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
            $this->path = rawurldecode($path);
        }

        if ($queryString = parse_url($dsn, PHP_URL_QUERY)) {
            $this->queryString = $queryString;

            $this->query = $this->httpParseQuery($queryString, '&', PHP_QUERY_RFC3986);
        }
    }

    /**
     * based on http://php.net/manual/en/function.parse-str.php#119484 with some slight modifications.
     */
    private function httpParseQuery(string $queryString, string $argSeparator = '&', int $decType = PHP_QUERY_RFC1738): array
    {
        $result = [];
        $parts = explode($argSeparator, $queryString);

        foreach ($parts as $part) {
            list($paramName, $paramValue) = explode('=', $part, 2);

            switch ($decType) {
                case PHP_QUERY_RFC3986:
                    $paramName = rawurldecode($paramName);
                    $paramValue = rawurldecode($paramValue);
                    break;
                case PHP_QUERY_RFC1738:
                default:
                    $paramName = urldecode($paramName);
                    $paramValue = urldecode($paramValue);
                    break;
            }

            if (preg_match_all('/\[([^\]]*)\]/m', $paramName, $matches)) {
                $paramName = substr($paramName, 0, strpos($paramName, '['));
                $keys = array_merge([$paramName], $matches[1]);
            } else {
                $keys = [$paramName];
            }

            $target = &$result;

            foreach ($keys as $index) {
                if ('' === $index) {
                    if (is_array($target)) {
                        $intKeys = array_filter(array_keys($target), 'is_int');
                        $index = count($intKeys) ? max($intKeys) + 1 : 0;
                    } else {
                        $target = [$target];
                        $index = 1;
                    }
                } elseif (isset($target[$index]) && !is_array($target[$index])) {
                    $target[$index] = [$target[$index]];
                }

                $target = &$target[$index];
            }

            if (is_array($target)) {
                $target[] = $paramValue;
            } else {
                $target = $paramValue;
            }
        }

        return $result;
    }
}
