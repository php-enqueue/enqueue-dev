<?php

namespace Enqueue\Test;

trait ClassExtensionTrait
{
    public function assertClassExtends($expected, $actual)
    {
        $rc = new \ReflectionClass($actual);

        $this->assertTrue(
            $rc->isSubclassOf($expected),
            sprintf('Failed assert that class %s extends %s class', $actual, $expected)
        );
    }

    public function assertClassImplements($expected, $actual)
    {
        $rc = new \ReflectionClass($actual);

        $this->assertTrue(
            $rc->implementsInterface($expected),
            sprintf('Failed assert that class %s implements %s interface.', $actual, $expected)
        );
    }

    public function assertClassFinal($actual)
    {
        $rc = new \ReflectionClass($actual);

        $this->assertTrue(
            $rc->isFinal(),
            sprintf('Failed assert that class %s is final.', $actual)
        );
    }
}
