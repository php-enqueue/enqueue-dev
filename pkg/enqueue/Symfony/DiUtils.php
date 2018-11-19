<?php

namespace Enqueue\Symfony;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class DiUtils
{
    public const DEFAULT_CONFIG = 'default';

    /**
     * @var string
     */
    private $moduleName;

    /**
     * @var string
     */
    private $configName;

    public function __construct(string $moduleName, string $configName)
    {
        $this->moduleName = $moduleName;
        $this->configName = $configName;
    }

    public static function create(string $moduleName, string $configName): self
    {
        return new static($moduleName, $configName);
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getConfigName(): string
    {
        return $this->configName;
    }

    public function reference(string $serviceName, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): Reference
    {
        return new Reference($this->format($serviceName), $invalidBehavior);
    }

    public function referenceDefault(string $serviceName, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): Reference
    {
        return new Reference($this->formatDefault($serviceName), $invalidBehavior);
    }

    public function parameter(string $serviceName): string
    {
        $fullName = $this->format($serviceName);

        return "%$fullName%";
    }

    public function parameterDefault(string $serviceName): string
    {
        $fullName = $this->formatDefault($serviceName);

        return "%$fullName%";
    }

    public function format(string $serviceName): string
    {
        return $this->doFormat($this->moduleName, $this->configName, $serviceName);
    }

    public function formatDefault(string $serviceName): string
    {
        return $this->doFormat($this->moduleName, self::DEFAULT_CONFIG, $serviceName);
    }

    private function doFormat(string $moduleName, string $configName, string $serviceName): string
    {
        return sprintf('enqueue.%s.%s.%s', $moduleName, $configName, $serviceName);
    }
}
