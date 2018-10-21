<?php

namespace Enqueue\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

trait FormatTransportNameTrait
{
    abstract protected function getName(): string;

    private function reference(string $serviceName, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): Reference
    {
        return new Reference($this->format($serviceName), $invalidBehavior);
    }

    private function parameter(string $serviceName): string
    {
        $fullName = $this->format($serviceName, false);

        return "%$fullName%";
    }

    private function format(string $serviceName, $parameter = false): string
    {
        $pattern = 'enqueue.transport.%s.'.$serviceName;

        $fullName = sprintf($pattern, $this->getName());

        return $parameter ? "%$fullName%" : $fullName;
    }
}
