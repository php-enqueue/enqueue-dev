<?php

namespace Enqueue\Util;

/**
 * This is used to log message parts when in debug mode.
 */
class Stringify
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        if (is_string($this->value) || is_scalar($this->value)) {
            return $this->value;
        }

        return json_encode($this->value, JSON_UNESCAPED_SLASHES);
    }

    public static function that($value): self
    {
        return new static($value);
    }
}
