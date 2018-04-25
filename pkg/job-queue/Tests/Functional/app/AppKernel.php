<?php

Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * @return array
     */
    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
        ];

        return $bundles;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/EnqueueJobQueue/cache';
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/EnqueueJobQueue/cache/logs';
    }

    /**
     * @param \Symfony\Component\Config\Loader\LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }

    protected function getKernelParameters()
    {
        $parameters = parent::getKernelParameters();

        // it works in all Symfony version, 2.8, 3.x, 4.x
        $parameters['db.driver'] = getenv('DOCTRINE_DRIVER');
        $parameters['db.host'] = getenv('DOCTRINE_HOST');
        $parameters['db.port'] = getenv('DOCTRINE_PORT');
        $parameters['db.name'] = getenv('DOCTRINE_DB_NAME');
        $parameters['db.user'] = getenv('DOCTRINE_USER');
        $parameters['db.password'] = getenv('DOCTRINE_PASSWORD');

        return $parameters;
    }

    protected function getContainerClass()
    {
        return parent::getContainerClass().'JobQueue';
    }
}
