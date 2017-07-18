<?php

namespace Enqueue\LaravelQueue;

use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $manager = $this->app['queue'];

        $manager->addConnector('enqueue', function () {
            return new DsnToPsrQueueConnector();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
    }
}
