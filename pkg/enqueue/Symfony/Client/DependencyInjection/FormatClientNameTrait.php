<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

trait FormatClientNameTrait
{
    abstract protected function getName(): string;

    private function reference(string $serviceName, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): Reference
    {
        return new Reference($this->format($serviceName), $invalidBehavior);
    }

    private function parameter(string $serviceName): string
    {
        $fullName = $this->format($serviceName);

        return "%$fullName%";
    }

    private function format(string $serviceName): string
    {
        $pattern = 'enqueue.client.%s.'.$serviceName;

        return sprintf($pattern, $this->getName());
    }
}
