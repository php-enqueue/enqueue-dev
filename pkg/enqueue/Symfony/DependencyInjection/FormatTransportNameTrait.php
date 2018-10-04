<?php

namespace Enqueue\Symfony\DependencyInjection;

trait FormatTransportNameTrait
{
    abstract protected function getName(): string;

    private function format(string $serviceName, $parameter = false): string
    {
        $pattern = 'enqueue.transport.%s.'.$serviceName;

        $fullName = sprintf($pattern, $this->getName());

        return $parameter ? "%$fullName%" : $fullName;
    }
}
