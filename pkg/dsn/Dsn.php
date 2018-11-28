<?php

declare(strict_types=1);

namespace Enqueue\Dsn;

class Dsn
{
    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $schemeProtocol;

    /**
     * @var string[]
     */
    private $schemeExtensions;

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
     * @var QueryBag
     */
    private $queryBag;

    public function __construct(
        string $scheme,
        string $schemeProtocol,
        array $schemeExtensions,
        ?string $user,
        ?string $password,
        ?string $host,
        ?int $port,
        ?string $path,
        ?string $queryString,
        array $query
    ) {
        $this->scheme = $scheme;
        $this->schemeProtocol = $schemeProtocol;
        $this->schemeExtensions = $schemeExtensions;
        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->queryString = $queryString;
        $this->queryBag = new QueryBag($query);
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

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

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

    public function getQueryBag(): QueryBag
    {
        return $this->queryBag;
    }

    public function getQuery(): array
    {
        return $this->queryBag->toArray();
    }

    public function getString(string $name, string $default = null): ?string
    {
        return $this->queryBag->getString($name, $default);
    }

    public function getDecimal(string $name, int $default = null): ?int
    {
        return $this->queryBag->getDecimal($name, $default);
    }

    public function getOctal(string $name, int $default = null): ?int
    {
        return $this->queryBag->getOctal($name, $default);
    }

    public function getFloat(string $name, float $default = null): ?float
    {
        return $this->queryBag->getFloat($name, $default);
    }

    public function getBool(string $name, bool $default = null): ?bool
    {
        return $this->queryBag->getBool($name, $default);
    }

    public function getArray(string $name, array $default = []): QueryBag
    {
        return $this->queryBag->getArray($name, $default);
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
            'query' => $this->queryBag->toArray(),
        ];
    }

    public static function parseFirst(string $dsn): ?self
    {
        return self::parse($dsn)[0];
    }

    /**
     * @param string $dsn
     *
     * @return Dsn[]
     */
    public static function parse(string $dsn): array
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
        $schemeProtocol = $schemeParts[0];

        unset($schemeParts[0]);
        $schemeExtensions = array_values($schemeParts);

        $user = parse_url($dsn, PHP_URL_USER) ?: null;
        if (is_string($user)) {
            $user = rawurldecode($user);
        }

        $password = parse_url($dsn, PHP_URL_PASS) ?: null;
        if (is_string($password)) {
            $password = rawurldecode($password);
        }

        $path = parse_url($dsn, PHP_URL_PATH) ?: null;
        if ($path) {
            $path = rawurldecode($path);
        }

        $query = [];
        $queryString = parse_url($dsn, PHP_URL_QUERY) ?: null;
        if (is_string($queryString)) {
            $query = self::httpParseQuery($queryString, '&', PHP_QUERY_RFC3986);
        }
        $hostsPorts = '';
        if (0 === strpos($dsnWithoutScheme, '//')) {
            $dsnWithoutScheme = substr($dsnWithoutScheme, 2);
            $dsnWithoutUserPassword = explode('@', $dsnWithoutScheme, 2);
            $dsnWithoutUserPassword = 2 === count($dsnWithoutUserPassword) ?
                $dsnWithoutUserPassword[1] :
                $dsnWithoutUserPassword[0]
            ;

            list($hostsPorts) = explode('#', $dsnWithoutUserPassword, 2);
            list($hostsPorts) = explode('?', $hostsPorts, 2);
            list($hostsPorts) = explode('/', $hostsPorts, 2);
        }

        if (empty($hostsPorts)) {
            return [
                new self(
                    $scheme,
                    $schemeProtocol,
                    $schemeExtensions,
                    null,
                    null,
                    null,
                    null,
                    $path,
                    $queryString,
                    $query
                ),
            ];
        }

        $dsns = [];
        $hostParts = explode(',', $hostsPorts);
        foreach ($hostParts as $key => $hostPart) {
            unset($hostParts[$key]);

            $parts = explode(':', $hostPart, 2);
            $host = $parts[0];

            $port = null;
            if (isset($parts[1])) {
                $port = (int) $parts[1];
            }

            $dsns[] = new self(
                $scheme,
                $schemeProtocol,
                $schemeExtensions,
                $user,
                $password,
                $host,
                $port,
                $path,
                $queryString,
                $query
            );
        }

        return $dsns;
    }

    /**
     * based on http://php.net/manual/en/function.parse-str.php#119484 with some slight modifications.
     */
    private static function httpParseQuery(string $queryString, string $argSeparator = '&', int $decType = PHP_QUERY_RFC1738): array
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
