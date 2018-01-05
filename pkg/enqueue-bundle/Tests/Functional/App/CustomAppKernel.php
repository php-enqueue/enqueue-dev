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
        'client' => [
            'prefix' => 'enqueue',
            'app_name' => '',
            'router_topic' => 'test',
            'router_queue' => 'test',
            'default_processor_queue' => 'test',
        ],
    ];

    public function setEnqueueConfig(array $config)
    {
        $this->enqueueConfig = array_replace_recursive($this->enqueueConfig, $config);
        $this->enqueueConfig['client']['app_name'] = str_replace('.', '', uniqid(true));
        $this->enqueueConfigId = md5(json_encode($this->enqueueConfig));

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/EnqueueBundleCustom/cache/'.$this->enqueueConfigId);
        $fs->mkdir(sys_get_temp_dir().'/EnqueueBundleCustom/cache/'.$this->enqueueConfigId);
    }

    /**
     * @return array
     */
    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Enqueue\Bundle\EnqueueBundle(),
        ];

        return $bundles;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/EnqueueBundleCustom/cache/'.$this->enqueueConfigId;
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/EnqueueBundleCustom/cache/logs/'.$this->enqueueConfigId;
    }

    protected function getContainerClass()
    {
        return parent::getContainerClass().'Custom'.$this->enqueueConfigId;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/custom-config.yml');

        $c->loadFromExtension('enqueue', $this->enqueueConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }
}
