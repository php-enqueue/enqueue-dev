<?php

declare(strict_types=1);

namespace Enqueue\Dsn;

class QueryBag
{
    /**
     * @var array
     */
    private $query;

    public function __construct(array $query)
    {
        $this->query = $query;
    }

    public function toArray(): array
    {
        return $this->query;
    }

    public function getString(string $name, string $default = null): ?string
    {
        return array_key_exists($name, $this->query) ? $this->query[$name] : $default;
    }

    public function getDecimal(string $name, int $default = null): ?int
    {
        $value = $this->getString($name);
        if (null === $value) {
            return $default;
        }

        if (false == preg_match('/^[\+\-]?[0-9]*$/', $value)) {
            throw InvalidQueryParameterTypeException::create($name, 'decimal');
        }

        return (int) $value;
    }

    public function getOctal(string $name, int $default = null): ?int
    {
        $value = $this->getString($name);
        if (null === $value) {
            return $default;
        }

        if (false == preg_match('/^0[\+\-]?[0-7]*$/', $value)) {
            throw InvalidQueryParameterTypeException::create($name, 'octal');
        }

        return intval($value, 8);
    }

    public function getFloat(string $name, float $default = null): ?float
    {
        $value = $this->getString($name);
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
        $value = $this->getString($name);
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

    public function getArray(string $name, array $default = []): self
    {
        if (false == array_key_exists($name, $this->query)) {
            return new self($default);
        }

        $value = $this->query[$name];

        if (is_array($value)) {
            return new self($value);
        }

        throw InvalidQueryParameterTypeException::create($name, 'array');
    }
}
