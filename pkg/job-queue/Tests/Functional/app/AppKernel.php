<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

// doctrine/annotations:2 autoloads annotations and removes loader registration
if (method_exists(AnnotationRegistry::class, 'registerLoader')) {
    AnnotationRegistry::registerLoader('class_exists');
}

class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new DoctrineBundle(),
        ];

        return $bundles;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/EnqueueJobQueue/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/EnqueueJobQueue/cache/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (self::VERSION_ID < 60000) {
            $loader->load(__DIR__.'/config/config-sf5.yml');

            return;
        }

        $loader->load(__DIR__.'/config/config.yml');
    }

    protected function getContainerClass(): string
    {
        return parent::getContainerClass().'JobQueue';
    }
}
