<?php

namespace Enqueue\Util;

/**
 * This is used to log message parts when in debug mode.
 */
class VarExport
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

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) var_export($this->value, true);
    }
}
