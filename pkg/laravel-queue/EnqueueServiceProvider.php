<?php

namespace Enqueue\LaravelQueue;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

class EnqueueServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        /** @var QueueManager $manager */
        $manager = $this->app['queue'];

        $manager->addConnector('interop', function () {
            return new Connector();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
    }
}
