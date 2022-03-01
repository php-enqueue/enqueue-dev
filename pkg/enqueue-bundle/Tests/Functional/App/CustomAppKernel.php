<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class CustomAppKernel extends Kernel
{
    use MicroKernelTrait;

    private $enqueueConfigId;

    private $enqueueConfig = [
        'default' => [
            'client' => [
                'prefix' => 'enqueue',
                'app_name' => '',
                'router_topic' => 'test',
                'router_queue' => 'test',
                'default_queue' => 'test',
            ],
        ],
    ];

    public function setEnqueueConfig(array $config)
    {
        $this->enqueueConfig = array_replace_recursive($this->enqueueConfig, $config);
        $this->enqueueConfig['default']['client']['app_name'] = str_replace('.', '', uniqid('app_name', true));
        $this->enqueueConfigId = md5(json_encode($this->enqueueConfig));

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/EnqueueBundleCustom/cache/'.$this->enqueueConfigId);
        $fs->mkdir(sys_get_temp_dir().'/EnqueueBundleCustom/cache/'.$this->enqueueConfigId);
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Enqueue\Bundle\EnqueueBundle(),
        ];

        return $bundles;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/EnqueueBundleCustom/cache/'.$this->enqueueConfigId;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/EnqueueBundleCustom/cache/logs/'.$this->enqueueConfigId;
    }

    protected function getContainerClass(): string
    {
        return parent::getContainerClass().'Custom'.$this->enqueueConfigId;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        if (self::VERSION_ID < 60000) {
            $loader->load(__DIR__.'/config/custom-config-sf5.yml');
        } else {
            $loader->load(__DIR__.'/config/custom-config.yml');
        }

        $c->loadFromExtension('enqueue', $this->enqueueConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }
}
