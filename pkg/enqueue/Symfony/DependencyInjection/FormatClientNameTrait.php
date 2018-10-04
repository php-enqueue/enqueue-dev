<?php

namespace Enqueue\Symfony\DependencyInjection;

trait FormatClientNameTrait
{
    abstract protected function getName(): string;

    private function format(string $serviceName, $parameter = false): string
    {
        $pattern = 'enqueue.client.%s.'.$serviceName;

        $fullName = sprintf($pattern, $this->getName());

        return $parameter ? "%$fullName%" : $fullName;
    }
}
