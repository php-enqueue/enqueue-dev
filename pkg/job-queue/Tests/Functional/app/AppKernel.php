<?php

if (class_exists(Doctrine\Common\Annotations\AnnotationRegistry::class)
    && method_exists(Doctrine\Common\Annotations\AnnotationRegistry::class, 'registerLoader')) {
    Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
}

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
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
